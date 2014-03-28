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

#Check ACL

#Update source.list

#Provide a cert to Dovecot

#Kill Courier

#Disable Courier


