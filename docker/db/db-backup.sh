#!/bin/bash

if [[ "x${MYSQL_HOST}" == "x" ]]; then
        echo "MYSQL_HOST missing"
        exit 1
fi

if [[ "x${MYSQL_DATABASE}" == "x" ]]; then
        echo "MYSQL_DATABASE missing"
        exit 1
fi

if [[ "x${MYSQL_USER}" == "x" ]]; then
        echo "MYSQL_USER missing"
        exit 1
fi

if [[ "x${MYSQL_PASSWORD}" == "x" ]]; then
        echo "MYSQL_PASSWORD missing"
        exit 1
fi

MYSQL_CONFIG_FILE=/root/.my.cnf

touch ${MYSQL_CONFIG_FILE}
chmod 600 ${MYSQL_CONFIG_FILE}
cat <<EOF > ${MYSQL_CONFIG_FILE}
[mysqldump]
user=${MYSQL_USER}
password=${MYSQL_PASSWORD}
EOF

mysqldump -h ${MYSQL_HOST} ${MYSQL_DATABASE}

rm -rf ${MYSQL_CONFIG_FILE}
