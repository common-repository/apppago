<?php


class WC_APPpago_Logger
{
    /**
     * Funzione di logging Generica
     * @param $message
     * @param $gravity:
     *     'emergency': System is unusable.
     *     'alert': Action must be taken immediately.
     *     'critical': Critical conditions.
     *     'error': Error conditions.
     *     'warning': Warning conditions.
     *     'notice': Normal but significant condition.
     *     'info': Informational messages.
     *     'debug': Debug-level messages.
     */
    static public function Log($message, $gravity = 'info')
    {
        $logger = wc_get_logger();
        $context = array( 'source' => 'APPPAGO' );
        $logger->log($gravity, 'APPPAGO - ' . $message, $context);
    }

    /**
     * Funzione di logging per le exception di tipo Warning
     * @param $exception
     */
    static public function LogExceptionWarning($exception)
    {
        static::LogException($exception, 'warning');
    }

    /**
     * Funzione di logging per le exception di tipo Error
     * @param $exception
     */
    static public function LogExceptionError($exception)
    {
        static::LogException($exception, 'error');
    }

    /**
     * Funzione di logging per le exception di tipo Critical
     * @param $exception
     */
    static public function LogExceptionCritical($exception)
    {
        static::LogException($exception, 'critical');
    }

    /**
     * Funzione privata che formatta il messaggio per il logging delle eccezioni
     * @param $exception
     * @param $gravity
     */
    static private function LogException($exception, $gravity)
    {
        $msg = $exception->getMessage() . ' - ' . $exception->getFile() . ':' . $exception->getLine();
        static::Log($msg, $gravity);
        if ($exception->getPrevious()) {
            static::LogException($exception->getPrevious());
        }
    }
}
