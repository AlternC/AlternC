<?php
interface vm {
	public function start();

	/**
	 * @return boolean
	 */
	public function stop();
}
