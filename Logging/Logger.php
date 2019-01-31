<?php

class Logger {

    protected $level;
    protected $file_name;
    protected $error_log;
    protected $debug_log;
    protected $hostname;
    protected $pid;

    function __construct($file_name = "", $debug_level = 0) {

        //_Log File Definitions =========================
        $this->debug_log = "/path/to/debug.log";
        $this->debug_log_json = "/path/to/debug.json";
        $this->error_log = "/path/to/error.log";
        $this->error_log_json = "/path/to/error.json";
        //===============================================

        $this->pid = posix_getpid();
        if($this->pid === FALSE) {
            $this->pid = -1;
        }

        $this->hostname = gethostname();
        if($this->hostname === FALSE) {
            $this->hostname = "(none)";
        } else {
            $this->hostname = substr($this->hostname, 0, strpos($this->hostname, "."));
        }

        if(is_int($file_name)) {
            $this->level = $file_name;
        } else {
            $this->level = $debug_level;
        }

    }

    /**
     * debug()
     *
     * Prints a debug message in the format of <timestamp> <hostname> from_file[pid]: [DEBUG] (line) <message>
     *
     * @param int $level Debug log level for the message
     * @param string $message The debug message to print
     * @return void
     * @throws Exception
     */
    public function debug($level, $message) {

        $level_type = gettype($level);

        try {
            if($level_type !== "integer") {
                throw new Exception(__CLASS__ ."->" . __FUNCTION__ . ": Invalid parameter type received for level! Expected integer but received $level_type.", 1);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $message = var_export($message, true);

        if ($this->level >= $level) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
            $from_file = $this->file_name;
            $actual_file = substr($bt[0]["file"], strrpos($bt[0]["file"], "/") + 1);
            if ($this->file_name !== $actual_file) {
                $from_file .= "($actual_file)";
            }
            $line_num = $bt[0]["line"];
            if (is_object($message) || is_array($message)) {

                $message = print_r( $message, true );
                file_put_contents($this->debug_log, date("M d H:i:s") . " $this->hostname $from_file".'['.$this->pid."]: [DEBUG] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

                $this->log_json($this->debug_log_json, $actual_file, $line_num, 'DEBUG', $message);

            } else {

                file_put_contents($this->debug_log, date("M d H:i:s") . " $this->hostname $from_file" . '[' . $this->pid . "]: [DEBUG] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

                $this->log_json($this->debug_log_json, $actual_file, $line_num, 'DEBUG', $message);

            }

        }

    }

    /**
     * error()
     *
     * Logs and handles different error levels
     * Prints an error message in the format of <timestamp> <hostname> from_file[pid]: [error_level] (line) <message>
     * Error levels:
     *      1 - WARN - Warning level errors
     *      2 - ERROR - Standard errors
     *      3 - FATAL - Fatal errors, will call exit() and throw an error to the ui
     *
     * @param int $level The error level
     * @param string $message The error message
     *
     * @return void
     * @throws Exception
     */
    public function error($level, $message) {

        $level_type = gettype($level);

        try {
            if($level_type !== "integer") {
                throw new Exception(__CLASS__ ."->" . __FUNCTION__ . ": Invalid parameter type received for level! Expected integer but received $level_type.", 1);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $message = var_export($message, true);

        $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1);
        $from_file = $this->file_name;
        $actual_file = substr($bt[0]["file"], strrpos($bt[0]["file"], "/") + 1);
        if($this->file_name !== $actual_file) {
            $from_file .= "($actual_file)";
        }
        $line_num = $bt[0]["line"];

        $message_header = date("M d H:i:s") . " $this->hostname $from_file".'['.$this->pid."]:";

        switch($level) {
            case '1':
                $temp_level = "WARN";
                break;
            case '2':
                $temp_level = "ERROR";
                break;
            case '3':
                $temp_level = "FATAL";

                if (is_object($message) || is_array($message)) {

                    $message = print_r($message, true);
                    file_put_contents($this->error_log, "$message_header [$temp_level] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

                    $this->log_json($this->error_log_json, $actual_file, $line_num, $temp_level, $message);

                } else {

                    file_put_contents($this->error_log, "$message_header [$temp_level] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

                    $this->log_json($this->error_log_json, $actual_file, $line_num, $temp_level, $message);

                }

                header("[$temp_level] $message", true, 500);
                echo "[$temp_level] $message";
                exit(0);

        }

        if (is_object($message) || is_array($message)) {

            $message = print_r( $message, true );
            file_put_contents($this->error_log, "$message_header [$temp_level] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

            $this->log_json($this->error_log_json, $actual_file, $line_num, $temp_level, $message);

        } else {

            file_put_contents($this->error_log, "$message_header [$temp_level] ($line_num) $message\n", FILE_APPEND | LOCK_EX);

            $this->log_json($this->error_log_json, $actual_file, $line_num, $temp_level, $message);

        }

    }

    /**
     * log_json()
     *
     * takes the same logging information as debug/error and formats it for better kibana digestion
     *
     * @param $log_path
     * @param $actual_file
     * @param $line_num
     * @param $temp_level
     * @param $message
     */
    private function log_json($log_path, $actual_file, $line_num, $temp_level, $message) {

        $msg['date'] = date("M d H:i:s");
        $msg['hostname'] = $this->hostname;
        $msg['file'] = $actual_file;
        $msg['pid'] = $this->pid;
        $msg['line'] = $line_num;
        $msg['log'] = $temp_level;
        $msg['message'] = $message;

        file_put_contents($log_path, json_encode($msg) . "\n", FILE_APPEND | LOCK_EX);

    }

}
