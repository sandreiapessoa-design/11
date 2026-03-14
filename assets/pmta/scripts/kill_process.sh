#!/bin/bash kill_process.sh pid
kill $(ps aux | awk '{print $2 }' | grep -w $1)