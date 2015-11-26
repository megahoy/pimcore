<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Schedule\Manager;

use Pimcore\Model;

declare(ticks = 1);

class Daemon extends Procedural {

    /**
     * @var int
     */
    public $maxProcesses = 25;

    /**
     * @var int
     */
    protected $jobsStarted = 0;

    /**
     * @var array
     */
    protected $currentJobs = array();

    /**
     * @var array
     */
    protected $signalQueue = array();

    /**
     * @var int
     */
    protected $parentPID;

    /**
     * @var bool
     */
    protected $waitForChildrenToFinish;

    /**
     * @param  bool $wait
     * @return void
     */
    public function setWaitForChildrenToFinish($wait){
        $this->waitForChildrenToFinish=$wait;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWaitForChildrenToFinish(){
        return $this->waitForChildrenToFinish;
    }

    public function __construct($pidFileName, $waitForChildrenToFinish = true) {

        if(!function_exists("pcntl_fork") or !function_exists("pcntl_waitpid") or !function_exists("pcntl_wexitstatus") or !function_exists("pcntl_signal")){
            //throw exception if someone tries to instantiate this without having pcntl enabled
            throw new \Exception("pcntnl not available. Cannot create ");
        }

        parent::__construct($pidFileName);
        $this->parentPID = getmypid();
        $this->waitForChildrenToFinish = $waitForChildrenToFinish;
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));       
    }

    /**
     * runs all jobs in the queue. depending on the property $waitForChildrenToFinish (specify in constructor!)
     * it waits for child processes to finish their tasks or not
     * @return void
     */
    public function run() {
        \Logger::info("Running with PID " . $this->parentPID);
        $this->setLastExecution();

        foreach ($this->jobs as $job) {
            $this->launchJob($job);
            \Logger::info("Finished job with ID: " . $job->getId());
        }

        if($this->waitForChildrenToFinish){
            //Wait for child processes to finish
            while (count($this->currentJobs)) {
                sleep(1);
            }
            \Logger::info("finished Jobs, all child threads are done.");
        } else {
            \Logger::info("spawned all children, not waiting for them to finish.");
        }

    }

    /**
     * @param $job
     * @return bool
     */
    protected function launchJob($job) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            //Problem launching the job
            \Logger::error('Could not launch new job with id [ ' . $job->getId() . ' ], exiting');
            return false;
        }
        else if ($pid) {
            $this->currentJobs[$pid] = $job->getId();
          if (isset($this->signalQueue[$pid])) {
                $this->childSignalHandler(SIGCHLD, $pid, $this->signalQueue[$pid]);
                unset($this->signalQueue[$pid]);
            }
        }
        else {
            //Forked child
            try {
                \Pimcore\Db::reset(); // reset resource
                \Pimcore::initLogger(); // reinit logger so that he gets a different token eg for mailing
                \Logger::debug("Executing job [ " . $job->getId() . " ] as forked child");
                $job->execute();
            } catch (\Exception $e) {
                \Logger::error($e);
                \Logger::error("Failed to execute job with id [ " . $job->getId() . " ] and method [ ".$job->getMethod()." ]");
            }
            $job->unlock();
            \Logger::debug("Done with job [ " . $job->getId() . " ]");
            exit(0);

        }
        return true;
    }

    /**
     * handle child finished
     * @param  $signo
     * @param  $pid
     * @param  $status
     * @return bool
     */
    public function childSignalHandler($signo, $pid = null, $status = null) {

        if (!$pid) {
            //got signal from system
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        //Make sure we get all of the exited children
        while ($pid > 0) {
            if ($pid && isset($this->currentJobs[$pid])) {
                $exitCode = pcntl_wexitstatus($status);
                if ($exitCode != 0) {
                    \Logger::error("$pid exited with status " . $exitCode);
                }
                unset($this->currentJobs[$pid]);
            }
            else if ($pid) {
                //job has finished before  parent process could note that it had even been launched
                $this->signalQueue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }
}
