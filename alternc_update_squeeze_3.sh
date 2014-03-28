#!/bin/bash -x

echo "Alternc update to 3"

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
    echo "Squeeze is required"
    exit;
fi

#Check version alternc
ALTERNC_VERSION="$(dpkg -l alternc|grep alternc|awk '{print $3}')"
ALTERNC_VERSION_MAJOR=${ALTERNC_VERSION:0:1}

if [ $ALTERNC_VERSION_MAJOR != 1 ]; then
    echo "Aternc 1.x required"
    exit;
fi

#Check alternc Directory
. /etc/alternc/local.sh

#Check ACL package
if [ -z "`type -f setfacl`" ]; then
    echo "acl package required"
    echo "apt-get install acl"
    exit
fi

#Check ACL configuration
aclcheckfile="${ALTERNC_LOC}/test-acl"
touch $aclcheckfile
setfacl -m u:root:rwx "$aclcheckfile" 2>/dev/null

if [ "$?" == 1 ]; then
    rm "$aclcheckfile"
    echo "ACL is not enabled on ${ALTERNC_LOC}"
    echo "According to your configuration, add acl or attr2 or user_attr directive on your /etc/fstab"
    echo "mount / -o remount,acl"
    exit
fi

rm "$aclcheckfile"


#Check sudo status/configuration
SUDO_VERSION="$(dpkg -l alternc|grep sudo|awk '{print $3}')"

if [ -z ${SUDO_VERSION} ]; then
    echo "Sudo is required"
    echo "apt-get install sudo"
    exit
fi

#Update source.list
echo "Source List update"
if [ -f /etc/apt/sources.list.d/alternc.list ]; then
    mv /etc/apt/sources.list.d/alternc.list "/etc/apt/sources.list.d/alternc.list.save".$(date +%s)
fi
echo "deb http://debian.alternc.org/ squeeze main" > /etc/apt/sources.list.d/alternc.list
wget http://debian.alternc.org/key.txt -O - | apt-key add -
apt-get update

#Provide a cert to Dovecot

#Kill Courier
echo "Kill courier service"
cd /etc/init.d/
./courier-authdaemon stop
./courier-imap stop
./courier-imap-ssl stop
./courier-pop stop
./courier-pop-ssl stop

#Disable Courier
echo "Disable pre remove Courrier package (Bug Debian)"
sed -i '1s/^/!#\/bin\/sh\nexit 0\n/' /var/lib/dpkg/info/courier-imap-ssl.prerm
sed -i '1s/^/!#\/bin\/sh\nexit 0\n/' /var/lib/dpkg/info/courier-imap.prerm
