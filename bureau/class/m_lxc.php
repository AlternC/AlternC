<?php

include_once(dirname(__FILE__) . '/vm.php');
# include('vm.php'); // This one fails ...

class m_lxc implements vm
{
	public function start()
	{
		echo "lxc::start";

	}

	public function monit()
	{
		echo "1 / 5 used";
	}

	public function stop()
	{
		echo "lxc::stop";

	}
}
