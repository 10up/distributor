for i in 1 2 3; do
	echo -e "\033[32mRunning wp-pacceptance with config $1\033[0m"
	./vendor/bin/wpacceptance run $1 -v
	EXIT_CODE=$?

	if [ $EXIT_CODE -gt 1 ]; then
		echo "Retrying..."
		sleep 3
	else
		break
	fi
done

exit $EXIT_CODE
