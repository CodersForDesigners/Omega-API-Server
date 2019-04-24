
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

