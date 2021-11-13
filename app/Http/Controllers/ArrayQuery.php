<?php
/**
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *                    Version 2, December 2004
 *
 * Copyright (C) 2012 Web Dev Guides <www.webdevguides.co.uk>
 *
 * Everyone is permitted to copy and distribute verbatim or modified
 * copies of this license document, and changing it is allowed as long
 * as the name is changed.
 *
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION
 *
 * 0. You just DO WHAT THE FUCK YOU WANT TO.
 */
 
 

/**
 * Class for doing SQL-like queries on arrays using callbacks.
 * Supports selecting, updating and deleting.
 *
 * An example, for selecting the names of employees whose job is "cleaner" and sorting by salary and then name descending:
 *
 *	$cleaners = Query::from($employees)
 *	->where(function($employee)
 *	{
 *		return $employee->getJob() == 'cleaner';
 *	})
 *	->order(function($employee)
 *	{
 *		return $employee->getSalary();
 *	})
 *	->orderDesc(function($employee))
 *	{
 *		return $employee->getName();
 *	})
 *	->select(function($employee)
 *	{
 *		return $employee->getName();
 *	});
 *
 * Note that passing a callback to select() is optional, and if left out will just return the array element in its entirety
 *
 * @package ArrayQuery
 * @author Web Dev Guides (www.webdevguides.co.uk)
 * @version 1.0.0
 * @link http://www.webdevguides.co.uk/php-2/arrayquery-phps-answer-to-linq
 */

namespace App\Http\Controllers;

class ArrayQuery extends Controller
{
	/**
	 * How many array elements were affected by the last delete/update
	 * @var int
	 */
	private $affected;

	/**
	 * The array that the query will be performed upon
	 * @var array
	 */
	private $array = array();
	
	/**
	 * Array of order callbacks added from order()
	 * @var array
	 */
	private $orderByFuncs = array();
	
	/**
	 * The calback that is added from where()
	 * @var callback
	 */
	private $whereFunc;

	/**
	 * Static function allowing easier interface when querying, for example:
	 * $result = Query::from($array)->select();
	 * instead of:
	 * $query = new Query($array); $result = $query->select();
	 *
	 * @param array $array - the array on which to perform the query
	 * @return Query
	 */
	public static function from($array)
	{
		return new self($array);
	}

	/**
	 * Create the query object. Query::from() preferred, see above
	 * @param array $array - the array on which to perform the query
	 */
	public function __construct($array)
	{
		$this->array = $array instanceof \Traversable ? iterator_to_array($array) : $array;
		
		if(!is_array($this->array))
		{
			throw new \InvalidArgumentException('$array is not an array or Traversable object');
		}
	}

	/**
	 * Specify a callback to order the elements of the array by. 
	 * The callback should take a single argument - a single element from the array and return the value to order by
	 * More than one order function can be added, with them evaluated in order of adding them, similar to SQL's ORDER BY
	 * @param callback $func
	 * @param bool $asc
	 * @return Query - fluent interface
	 */
	function order($func, $asc = true)
	{
		$orderByFuncs = &$this->orderByFuncs;
		$array = $this->array;
		$key = count($this->orderByFuncs);
	
		//create function from $func for use in uksort later on
		$this->orderByFuncs[] = function($key1, $key2) use($func, &$orderByFuncs, $array, $key, $asc)
		{
			$a = $array[$key1];
			$b = $array[$key2];
			
			//call the function on each element to get the values to compare
			$a = call_user_func($func, $a);
			$b = call_user_func($func, $b);

			//if values are the same, order by the next order function, otherwise order by array key to preserve order
			if($a == $b)
			{		
				if(isset($orderByFuncs[$key + 1]))
				{
					$nextFunc = $orderByFuncs[$key + 1];
					return $nextFunc($key1, $key2);
				}
				
				return ($key1 < $key2) ? -1 : 1;
			}
			
			//if $asc is true, then sort in ascending order, otherwise descending
			if($asc)
			{
				return ($a < $b) ? -1 : 1;
			}
		
			return ($a < $b) ? 1 : -1;
		};

		return $this;
	}

	/**
	 * Same as order(), but order the elements in reverse
	 * @param callback $func
	 * @return Query - fluent interface
	 */
	public function orderDesc($func)
	{
		return $this->order($func, false);
	}

	/**
	 * Specify a callback which should take a single argument; a single element from the queried array.
	 * Return true to match the element, false not to
	 * @param callback $func
	 * @return Query - fluent interface
	 */
	public function where($func)
	{
		$this->whereFunc = $func;
		return $this;
	}

	/**
	 * Specify a callback function to transform only the matched elements, or all elements if no where callback is specified
	 * The callback should take one argument - a single element of the array and return a value to replace it with
	 * @param callback $func
	 * @return array - the original array with the matched elements transformed
	 */
	public function update($func)
	{
		$this->affected = 0;

		if($this->whereFunc)
		{
			foreach($this->array as $key => $elem)
			{
				//check the elem meets the where
				if(call_user_func($this->whereFunc, $elem))
				{
					$this->affected++;
					$this->array[$key] = call_user_func($func, $elem);
				}
			}
		}
		else
		{
			$this->affected = count($this->array);
			$this->array = array_map($func, $this->array);
		}

		$this->whereFunc = null;
		return $this->select();
	}

	/**
	 * Deletes all elements matching the previously specified where function and returns the resulting array
	 * @return array - the original array with the matched elements removed
	 */
	public function delete()
	{
		if($this->whereFunc)
		{
			$this->affected = 0;
		
			foreach($this->array as $key => $elem)
			{
				//check the elem meets the where
				if(call_user_func($this->whereFunc, $elem))
				{
					$this->affected++;
					unset($this->array[$key]);
				}
			}
		}
		else
		{
			throw new \InvalidArgumentException('You have not specified a where function');
		}

		$this->whereFunc = null;
		return $this->select();
	}

	/**
	 * Return all matched elements, or all elements if no where specified
	 * @param callback $func - optional - if specified, transform all returned elements using the callback, otherwise return all matched elements
	 * @return array
	 */
	public function select($func = null)
	{
		//execute the where first
		if($this->whereFunc)
		{
			$this->array = array_filter($this->array, $this->whereFunc);
		}

		if($this->orderByFuncs)
		{
			//execute the order by functions on the data
			uksort($this->array, $this->orderByFuncs[0]);
		}
		
		//remap the array keys so they go 0, 1, 2 etc
		$this->array = array_values($this->array);

		if($func)
		{
			return array_map($func, $this->array);
		}
		
		return $this->array;
	}

	/**
	 * Same as select(), but deletes duplicate elements from result
	 * @param callback $func
	 * @return array
	 */
	public function selectDistinct($func = null)
	{
		$array = $this->select($func);
		$return = array();

		foreach($array as $value)
		{
			if(!in_array($value, $return))
			{
				$return[] = $value;
			}
		}

		return $return;
	}

	/**
	 * Same as select(), but only returns the first element
	 * @param callback $func
	 * @return mixed - the single element from the result
	 */
	public function selectOne($func = null)
	{
		if($array = $this->select($func))
		{
			return $array[0];
		}
	}
	
	/**
	 * Get the number of affected elements if there has been an update or delete
	 * @return int
	 */
	public function getAffected()
	{
		return $this->affected;
	}
}
?>
