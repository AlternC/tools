#!/bin/bash

usage(){

    echo "migrate <domain> <new_uid> <new_web_dir>"
    exit 1;
}

if [ -z $1 ] ; then echo "Missing parameter #1"; usage; fi;
if [ -z $2 ] ; then echo "Missing parameter #2"; usage; fi;
if [ -z $3 ] ; then echo "Missing parameter #3"; usage; fi;

domain=$1
uid=$2
web_dir=$3
old_id=
old_id_suffix=1
user=root
password=

echo "Moving the old apache directory"
old_dir=$(find /var/www -type d -name $1)
if [ ! -d $old_dir ] ; then
    echo "[!] Could not find the domain directory!"
    exit
fi
echo "... $old_dir to $3"
mv $old_dir $3

echo "Changing the owner of domain"
mysql -e "update alternc.domaines SET compte=$uid, dns_action='UPDATE' where domaine='$domain'";
if [ ! -z $? ] ; then 
    echo "[!] Failed";
fi;

echo "Changing the owner of sub domains"
mysql -e "update alternc.sub_domaines SET compte=$uid, web_action='UPDATE' where domaine='$domain'"
if [ ! -z $? ] ; then 
    echo "[!] Failed";
fi;

echo "Removing old apache vhost files"
old_templates=$(find /var/lib/alternc/apache-vhost/$old_id_suffix/$old_id -name "*$domain*")
if [ ! -z $? ] ; then
    echo "[!] Failed";
fi;

for template in $old_templates; do
    if [ -f $template ] ; then 
	echo "... removing $template"
	rm -f $template;
    else 
	echo "[!] $template is not a file"
    fi;
done;

echo "Updating alternc domains"
/usr/lib/alternc/update_domains.sh

echo "Fixing apache extended ACL for uid $uid"
/usr/lib/alternc/fixperms.sh -u $uid

echo "Changing owners of mailboxes"
mailboxList=$(mysql alternc -u $user --password=$password -s -N -e "select m.path from mailbox m join address a on m.address_id = a.id join domaines d ON a.domain_id = d.id join membres u on d.compte = u.uid where u.uid =$uid")
for mailbox in $mailboxList ; do
    if [ ! -d $mailbox ]; then 
	echo "$mailbox is not a valid folder. exiting;"
    else 
	echi "changing owner of $mailbox"
	chown -R $uid $mailbox
    fi;
done;

