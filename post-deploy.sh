
#! /bin/bash

while getopts "p:" opt; do
	case ${opt} in
		p )
			PROJECT_DIR=${OPTARG}
			;;
	esac
done

# Establish a symbolic link for the environment directory:
rm __environment
mkdir -p ../environment/${PROJECT_DIR}
ln -s ../environment/${PROJECT_DIR} __environment

# -/-/-/-/-
# Install the third-party packages
# -/-/-/-/-
npm install

# -/-/-/-/-
# Reload the node processes
# -/-/-/-/-
pm2 reload "cupid"

# -/-/-/-/-
# Set up all the scheduled tasks
# -/-/-/-/-
chmod 744 setup/zoho-refresh-api-tokens.php
php setup/zoho-refresh-api-tokens.php

if [ -f setup/tasks.crontab ]; then
	CURRENT_WORKING_DIR=`pwd`
	HOME=${CURRENT_WORKING_DIR}/__environment/logs
	CRON_ENV="\n\nPATH=/bin:/usr/bin:/usr/local/bin:${CURRENT_WORKING_DIR}/setup\nHOME=${HOME}\n";
	printf $CRON_ENV | cat - setup/tasks.crontab | tee tmp_crontab;
	rm setup/tasks.crontab;
	mv tmp_crontab setup/tasks.crontab;
	cp setup/tasks.crontab ../environment/scheduled-tasks/$PROJECT_DIR.crontab
	cat ../environment/scheduled-tasks/*.crontab | crontab -
fi
