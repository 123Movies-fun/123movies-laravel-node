<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Collective\Html\Eloquent\FormAccessible;
use DB;
use auth;
use App\Link; 
use App\Vote;
use App\Community;
use App\CommunityLink;

class VoteController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
        * API Vote Processing.
        *
        * @param  Request $request
        * @return Response
    */
    public function postVote($type, $id, Request $request) {
        $user = Auth::user();
        //$type = $request->input("type");
        $upVote = $this->upVote = $request->input("vote") ?  1 : 0;
        //$id = $request->input("id");
        
        /* Verify vote type */
        $dbType = "community_link_id";
        if(!in_array($type, array("link", "text_post"))) return response()->json(["errors"=> "Invalid vote type."]);
    
        $this->type = $type;
        /* Grab foreign object */
        if($type == "link") 
            $this->foreign_object = CommunityLink::find($id); //$(strtoupper($type))::find($id);

        /* Foreign Object Not found? */
        if(!$this->foreign_object)
            return response()->json(["errors"=> "Invalid vote object ID."]);

        /* Check for existing vote on this object by this user */
        $this->previous_vote = Vote::where([
            ["community_link_id", "=", $id],
            ["user_id", "=", $user->id]
        ])->first();

        /* If vote exists and value hasn't changed, delete vote (user undo action) */
        if($this->previous_vote && $this->previous_vote->value == $upVote) {
            $this->deleteVote();
            return response()->json(["success"=> true, "count"=>($this->foreign_object->net_votes)]);
        }

        /* If vote doesn't exist, create it */
        if(!$this->previous_vote) {
            $vote = new Vote;
            $data = [
                'user_id' => $user->id,
                $dbType => intVal($id),
                'value'=> ($upVote) ? true : 0
            ];
            $vote->fill($data);
            $vote->save();

        /* Vote found and value has changed, let's update it. */
        } else { 
            $this->previous_vote->value = $upVote;
            $this->previous_vote->save();
            $net_votes = ($upVote == 1) ? 2 : -2;
            $change_previous = true;
        }
        
        /* Calculate net votes for this call & updaet foreign object column counts */
        if(!isset($net_votes)) $net_votes = ($upVote == 1) ? 1 : -1;
        $this->foreign_object->increment("net_votes", $net_votes);
        $this->foreign_object->$type->increment("net_votes", $net_votes);
        
        /* Update net vote totals on foreign object and related $type model. */
        if($net_votes >= 1) {
             $this->foreign_object->increment("up_votes");
             $this->foreign_object->$type->increment("up_votes");
            if(isset($change_previous)) {
                $this->foreign_object->decrement("down_votes");
                $this->foreign_object->$type->decrement("down_votes");
            }
        } else {
            $this->foreign_object->increment("down_votes");
            $this->foreign_object->$type->increment("down_votes");
            if(isset($change_previous)) {
                $this->foreign_object->decrement("up_votes");
                $this->foreign_object->$type->decrement("up_votes");
            }
        }

        /* Update foreign object creators user vote count */
        $this->foreign_object->link->user->increment("post_net_votes", $net_votes);
        if($net_votes >= 1) {
             $this->foreign_object->link->user->increment("post_up_votes", abs($net_votes));
            if(isset($change_previous))
                $this->foreign_object->link->user->decrement("post_down_votes");
        } else {
             $this->foreign_object->link->user->increment("post_down_votes");
            if(isset($change_previous))
                 $this->foreign_object->link->user->decrement("post_up_votes");
        }
        
         return response()->json(["success"=> true, "count"=>($this->foreign_object->net_votes)]);
    }

    /* Delete an existing vote */
    public function deleteVote() {
        $type = $this->type;

        if(!$this->previous_vote->value) {
            $this->foreign_object->increment('net_votes');
            $this->foreign_object->$type->increment('net_votes');
            $this->foreign_object->link->user->increment("post_net_votes");

            $this->foreign_object->decrement('down_votes');
            $this->foreign_object->$type->decrement('down_votes');
            $this->foreign_object->link->user->decrement("post_down_votes");

        } else { 
            $this->foreign_object->decrement('net_votes');
            $this->foreign_object->$type->decrement('net_votes');
            $this->foreign_object->link->user->decrement("post_net_votes");

            $this->foreign_object->decrement('up_votes');
            $this->foreign_object->$type->decrement('up_votes');
            $this->foreign_object->link->user->decrement("post_up_votes");
        }

        $this->previous_vote->delete();
    }



    public function _score($upvotes = 0, $downvotes = 0) {
        return $upvotes - $downvotes;
    }

    public function _hotness($ups = 0, $downs = 0, $date = 0) {
        $dt = strtotime($date);
        $epoch = strtotime("1970-01-01");
        $td = $dt - $epoch;
        $days = intVal($td / 86400);
        $secs = fmod($td,86400);
        $microtime = fmod($microtime,86400*1000000);
        $td = $days * 86400 + $secs + (floatval($microtime) / 1000000);
        $seconds = $td-1134028003;

        $score = $ups - $downs;
        $order = log(max(abs($score), 1), 10);

        if($score >= 0) $sign = 1;
            elseif($score < 0) $sign = -1;
            else $sign = 0;
        
        $final_score = round($order + $sign * $seconds / 45000, 7);

        return $final_score;
    }

    //confidence sort based on http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
    public function _confidence($upvotes = 0, $downvotes = 0) {
        $n = $upvotes + $downvotes;
        if($n === 0) {
        return 0;
        }
        $z = 1.281551565545; // 80% confidence
        $p = floor($upvotes) / $n;
        $left = $p + 1/(2*$n)*$z*$z;
        $right = $z*sqrt($p*(1-$p)/$n + $z*$z/(4*$n*$n));
        $under = 1+1/$n*$z*$z;
        return ($left - $right) / $under;
    }

    public function controversy($upvotes = 0, $downvotes = 0) {
        return ($upvotes + $downvotes) / max(abs($this->_score($upvotes, $downvotes)), 1);
    }

    static function hotness($ups, $downs, $posted) {
        $dt = strtotime((string) $posted);
        $epoch = strtotime("1970-01-01");
        $td = $dt - $epoch;
        $days = intVal($td / 86400);
        $secs = fmod($td,86400);
        $microtime = fmod(0,86400*1000000);
        $td = $days * 86400 + $secs + (floatval($microtime) / 1000000);
        $seconds = $td-1134028003;

        $score = $ups - $downs;
        $order = log(max(abs($score), 1), 10);

        if($score >= 0) $sign = 1;
            elseif($score < 0) $sign = -1;
            else $sign = 0;
        
        $final_score = round($order + $sign * $seconds / 45000, 7);

        return $final_score;

        //return $this->_hotness($upvotes, $downvotes, $posted);
    }

    public function confidence($upvotes, $downvotes) {
        return $this->_confidence($upvotes, $downvotes);
    }
    
}
