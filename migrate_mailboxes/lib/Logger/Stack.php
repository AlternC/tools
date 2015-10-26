<?php

namespace Logger;

/**
 * Logger stack. Logs message for each inserted logger
 *
 * @version    0.7
 * @package    Logger
 *
 * @author     Matěj Humpál <finwe@finwe.info>
 * @copyright  Copyright (c) 2011 Matěj Humpál
 */
class Stack implements \Logger\ILogger
{

	/**
	 * @var array
	 */
	protected $loggers = array();

	/**
	 * @param array $loggers
	 */
	public function __construct(array $options)
	{
		if (isset($options['loggers'])) {
			$this->setLoggers($options['loggers']);
		}
	}

	/**
	 * @param \Logger\ILogger $logger
	 */
	public function addLogger(ILogger $logger)
	{
		$this->loggers[] = $logger;
	}

	/**
	 * @param array $loggers
	 */
	public function setLoggers($loggers)
	{
		$this->checkLoggers($loggers);
		$this->loggers = $loggers;
	}

	/**
	 * @see Logger\ILogger::logMessage()
	 *
	 * Logs message for each registered logger.
	 */
	public function logMessage($level, $message = NULL)
	{
		foreach ($this->loggers as $logger) {
			call_user_func_array(array($logger, 'logMessage'), func_get_args());
		}
	}

	/**
	 * @param array of Logger\ILogger $loggers
	 */
	private function checkLoggers($loggers)
	{
		array_walk(
			$loggers,
			function($logger, $key) {
				if (false === $logger instanceof ILogger) {
					throw new \InvalidArgumentException('Stack accepts only objects implementing \Logger\ILogger interface');
				}
			}
		);
	}

}
