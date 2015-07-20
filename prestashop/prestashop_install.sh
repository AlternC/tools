#!/bin/bash

#External ressource
. /etc/alternc/local.sh
. /usr/lib/alternc/functions.sh

PRESTASHOP_SRC="http://www.prestashop.com/xml/version.xml"
PRESTASHOP_XML=$(wget -qO- $PRESTASHOP_SRC)
PRESTASHOP_URL=$(grep -oPm1 "(?<=<link>)[^<]+" <<< "$PRESTASHOP_XML")
PRESTASHOP_MD5=$(grep -oPm1 "(?<=<md5>)[^<]+" <<< "$PRESTASHOP_XML")
PRESTASHOP_NAME=$(basename $PRESTASHOP_URL)

while getopts ":d:" opt; do
	case $opt in
		d)
			echo "Domain $OPTARG set" >&2
			DOMAIN=$OPTARG
			ALTERNC_LOGIN=$(get_account_by_domain $DOMAIN)
			ALTERNC_UID=$(get_uid_by_domain $DOMAIN)
		;;
		\?)
			echo "Invalid option: -$OPTARG" >&2
		;;
	esac
done

if [ -z "$ALTERNC_LOGIN" ]; then
	echo "Alternc user account not found"
	exit 1
fi

if [ $(grep -o "\." <<< "$DOMAIN" | wc -l) != 2 ]; then
	echo "Subdomain not set (www, shop, ...)"
	exit 1
fi

DOMAIN_SUB=$(echo $DOMAIN |cut -d '.' -f 1)

INITIALE=`echo $ALTERNC_LOGIN |cut -c1`
ALTERNC_SUBDIR=$(mysql_query 'SELECT valeur FROM sub_domaines WHERE compte="'"$ALTERNC_UID"'" AND sub="'"$DOMAIN_SUB"'" AND upper(type)="VHOST" ;')

ALTERNC_DIR="$ALTERNC_LOC/html/$INITIALE/$ALTERNC_LOGIN/$ALTERNC_SUBDIR"

if [ "$(ls -A $ALTERNC_DIR)" ]; then
	echo "Not Empty : ${ALTERNC_DIR}"
	exit 1
fi

wget -P $ALTERNC_DIR $PRESTASHOP_URL

PRESTASHOP_FILE=$ALTERNC_DIR/$PRESTASHOP_NAME
if [ "${PRESTASHOP_MD5}" != "$(md5sum $PRESTASHOP_FILE |cut -d ' ' -f 1 )" ]; then
	echo "Archive PS wrong md5"
	exit 1
fi

unzip $PRESTASHOP_FILE -d $ALTERNC_DIR
mv $ALTERNC_DIR/prestashop/* $ALTERNC_DIR/
rm $PRESTASHOP_FILE

#Create Database
DATABASE=($(./create_db.php -u $ALTERNC_UID))

if [ $? == "1" ]; then
        echo "Error in database creation"
        exit 1
fi


DB_NAME=${DATABASE[0]}
DB_USER=${DATABASE[1]}
DB_PWD=${DATABASE[2]}

#Install PS
res=$(/usr/bin/php $ALTERNC_DIR/install/index_cli.php \
	--domain=$DOMAIN \
	--newsletter=0 \
	--db_server=localhost \
	--db_name=$DB_NAME \
	--db_user=$DB_USER \
	--db_password=$DB_PWD)


#Purge directories
ADMIN_DIR="admin"$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 6 | head -n 1)
rm -r $ALTERNC_DIR/install
mv $ALTERNC_DIR/admin $ALTERNC_DIR/$ADMIN_DIR

/usr/lib/alternc/fixperms.sh -u $ALTERNC_UID

echo "Website : http://"$DOMAIN \
	"Admin url : http://"$DOMAIN"/"$ADMIN_DIR \
	"Account login    : pub@prestashop.com" \
	"Account password : 0123456789"
