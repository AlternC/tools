#!/bin/bash

echo "== Alternc update to 3"

#Initialization variables
IS_SQUEEZE=false

#Check squeeze status
if [ -f /etc/debian_version ]; then
    DEBIAN_VERSION="$(</etc/debian_version)"
    DEBIAN_VERSION_MAJOR=${DEBIAN_VERSION:0:1}
    if [ $DEBIAN_VERSION_MAJOR == 6 ]; then
        IS_SQUEEZE=true
    fi
fi

if [[ ${IS_SQUEEZE} == false ]]; then
    echo "NOK : Squeeze is required"
    exit;
fi

echo "OK : It's squeeze ( ${DEBIAN_VERSION} )"

#Check version alternc
ALTERNC_VERSION="$(dpkg -l alternc|grep alternc|awk '{print $3}')"
ALTERNC_VERSION_MAJOR=${ALTERNC_VERSION:0:1}

if [ $ALTERNC_VERSION_MAJOR != 1 ]; then
    echo "NOK : Aternc 1.x required"
    exit;
fi

echo "OK : It's alternc ( ${ALTERNC_VERSION} )"

#Check alternc Directory
. /etc/alternc/local.sh

#Check ACL package
if [ -z "`type -f setfacl 2>/dev/null`" ]; then
    echo "NOK : acl package required"
    echo "      apt-get install acl"
    exit;
fi

#Check ACL configuration
aclcheckfile="${ALTERNC_LOC}/test-acl"
touch $aclcheckfile
setfacl -m u:root:rwx "$aclcheckfile" 2>/dev/null

if [ "$?" == 1 ]; then
    rm "$aclcheckfile"
    echo "NOK : ACL is not enabled on ${ALTERNC_LOC}"
    echo "      According to your configuration, add acl or attr2 or user_attr directive on your /etc/fstab"
    echo "      mount / -o remount,acl"
    exit
fi

rm "$aclcheckfile"

echo "OK : ACL are enabled and configured"

#Check sudo status/configuration
SUDO_VERSION="$(dpkg -l sudo|grep sudo|awk '{print $3}')"

if [ -z ${SUDO_VERSION} ]; then
    echo "NOK : sudo is required"
    echo "      apt-get install sudo"
    exit
fi

#Check /etc/sudoers.d enable
grep -Fxq "#includedir /etc/sudoers.d" /etc/sudoers
if [ "$?" == 1 ]; then
    echo "#includedir /etc/sudoers.d added in /etc/sudoers"
    echo "#includedir /etc/sudoers.d" >> /etc/sudoers
fi

echo "OK : Sudo is enabled and compliant with alternc"

#Update source.list
echo "== source List update"
if [ -f /etc/apt/sources.list.d/alternc.list ]; then
    mv /etc/apt/sources.list.d/alternc.list "/etc/apt/sources.list.d/alternc.list.save".$(date +%s)
fi
echo "deb http://debian.alternc.org/ squeeze main" > /etc/apt/sources.list.d/alternc.list
wget --quiet http://debian.alternc.org/key.txt -O - | apt-key add - >/dev/null
apt-get update >/dev/null

echo "OK : Alternc repository is added and packages list updated"

#Check and restore courier status
COURIER_STATUS=`dpkg -l courier-imap|grep courier|awk '{print $1}'`

if [[ $COURIER_STATUS == "rF" ]]; then
    echo "== Courier status error, restore configuration"
    apt-get install courier-base courier-authlib courier-authdaemon courier-authlib-mysql courier-authlib-userdb courier-imap courier-pop courier-ssl -yq
    echo "OK : Courier restored"
fi

#Kill Courier
echo "== Kill courier service"
cd /etc/init.d/
./courier-authdaemon stop
./courier-imap stop
./courier-imap-ssl stop
./courier-pop stop
./courier-pop-ssl stop

#Disable Courier
echo "== Disable pre remove Courrier package (Bug Debian)"
sed -i '1s/^/#!\/bin\/sh\nexit 0\n/' /var/lib/dpkg/info/courier-imap-ssl.prerm
sed -i '1s/^/#!\/bin\/sh\nexit 0\n/' /var/lib/dpkg/info/courier-imap.prerm

echo "OK : Courier is disabled"


echo "Perfect : You can execute"
echo "      apt-get install alternc "
