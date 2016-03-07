#!/bin/bash
# This script set good ownership and good right for user's maildir
# This script must be launch by user root
# User root must be connect on AlternC database
# By Sébastien JEAN (aka Petit)
# 2016 02
# Version $VERSION

# VARS
VERSION="1.4"
alternc_conf="/etc/alternc/local.sh"

# FUNCTIONS 
usage(){
    cat <<EOF 
	Usage: ./$0 options

	This script set good ownership and good right for user's maildir 

	    Syntax :
	         ./$0 -a 
	         ./$0 -d <domain> 
	         ./$0 -m <account> 

		OPTIONS:
		    -h		        Show this message
		    -a			Make the job for all account
		    -d <domain>         Make the job for all account of <domain>
		    -m <mail account>	Make the job only for <mail account>
								
	Avalaible here: https://github.com/AlternC/alternc-tools/tree/master/migrate_mailboxes
	$0 Version $VERSION 
EOF
}

# MAIN
# Make sure only root can run our script
if [ "$(id -u)" != "0" ]
then
    echo "This script must be run as root"
    exit 1
fi

# Get path where alternc store maildir
if [ ! -f $alternc_conf ]
then 
    echo -e "Error: Couldn't find $alternc_conf file…\nExiting !" 
    exit 1    
fi

PATH_MAIL=`cat $alternc_conf |grep "^ALTERNC_MAIL"|cut -d "\"" -f2`
if [ -z $PATH_MAIL ]
then 
    echo -e "Error: Failed to find path mail"
fi

# Manage opts
if [ $# -eq 0 ];
then
    usage 
    exit 1
fi
while getopts "had:m:" OPTION
do
    case $OPTION in
	h|help)
	    usage
	    exit 0
	;;
	a)
	    find_opt="*"
	;;
	d)
	    find_opt="*_$OPTARG"
	;;
	m)
	    find_opt=`echo $OPTARG|sed -e "s/@/_/"`
	;;
	*)
	    usage
	;;
    esac
done

# It should find mailboxes in alternc mail path
    echo "#Fixing: "
find $PATH_MAIL -maxdepth 2 -mindepth 2 -type d -iname "${find_opt}" |while read mail
    do
    echo "$mail "
    domain=`basename $mail|sed -e "s/.*_\(.*\)$/\1/"`
    # It should find in alternc database the owner/uid for this domain 
    uid=`mysql alternc -ss -e "select compte from domaines where domaine='$domain';"`
    if [ ! -z $uid ] && [ ! -z $mail ]
    then
	# It should fix ownership on all mailbox file
	chown -R $uid:vmail $mail
	find $mail -type d -exec chmod 770 {} \;
	find $mail -type f -exec chmod 750 {} \;
    fi
done
