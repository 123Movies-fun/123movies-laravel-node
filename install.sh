#/bin/bash


##### Add nginx repos ######
sudo nano /etc/apt/sources.list
deb http://mirrors.digitalocean.com/debian jessie main contrib non-free
deb-src http://mirrors.digitalocean.com/debian jessie main contrib non-free

deb http://security.debian.org/ jessie/updates main contrib non-free
deb-src http://security.debian.org/ jessie/updates main contrib non-free

# jessie-updates, previously known as 'volatile'
deb http://mirrors.digitalocean.com/debian jessie-updates main contrib non-free
deb-src http://mirrors.digitalocean.com/debian jessie-updates main contrib non-free


## Update apt dict
sudo apt-get update


#install LEMP
sudo apt-get install php5-fpm php5-mysql


### EXTREMELY IMPORTANT FOR SECURITY!
sudo nano /etc/php5/fpm/php.ini
cgi.fix_pathinfo=0
sudo systemctl restart php5-fpm


<<<<<<<<<<<< Copy vhost here >>>>>>>>>>>>>>>>>>>>>


sudo systemctl reload nginx