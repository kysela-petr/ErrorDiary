<?php

namespace App\ErrorDiaryModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Application\Responses\TextResponse;

/**
 * Homepagepresenter.
 */
class HomepagePresenter extends Presenter {

	private $logDir;

	private $logFiles;

	public function __construct() {
		 $this->logDir = LOG_DIR;
		 $this->logFiles = [
			'critical' => '/critical.log',
			'error' => '/error.log',
			'exception' => '/exception.log',
			'access' => '/access.log',
			'info' => '/info.log',
			'email' => '/email-sent',
		];
	}

	public function beforeRender(){
        parent::beforeRender();

        if (!$this->getUser()->isLoggedIn()) {
			$this->redirectUrl('/');
		}
	}

	public function actionDefault() {
	}

	public function actionLog($type) {
		$logFile = $this->logDir . $this->logFiles[$type];
		$logEntries = [];
		
		try {

			if (file_exists($logFile)) {
				$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				foreach ($lines as $line) {
					$isDate = preg_match('^\[(.*)\]^', $line, $date);
					$text = preg_replace('^\[(.*)\]^', '', $line);
					$isException = preg_match('^exception[--](.*).html^', $line, $exception);
					$isExceptionId = preg_match('^exception[-0-9]{21}(.*).html^', $line, $exceptionId);
					$logEntries[] = [
						'entry' => $line,
						'date' => ($isDate) ? $date[1] : false,
						'title' => substr($text,0,35) . '...',
						'text' => ($text) ? $text : $line,
						'exception' => ($isException) ? $exception[0] : '', 
						'exceptionId' => ($isExceptionId) ? $exceptionId[1] : '' 
					];
				}
			}
			
		} catch (\Exception $e) {
			$this->flashMessage('Entries load error: ' . $e->getMessage(), 'danger');
			$this->redirect('Log:default');
		}

		$this->template->logType = $type;
		$this->template->logEntries = $logEntries;
	}

	public function actionTracy() {
		$tracyEntries = [];
		if ($handle = opendir($this->logDir)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != ".." && strpos($entry, '.html')) {
		            $tracyEntries[] = $entry;
		        }
		    }
		    closedir($handle);
		}
		$this->template->tracyEntries = $tracyEntries;

	}
	
	public function actionException($name) {
		try {
			$file = file($this->logDir . $name);
			$page = implode('', array_values($file));
			$this->template->html = $page;
		} catch (\Exception $e) {
			$this->presenter->sendResponse(new TextResponse("Can't open exception file: " . $e->getMessage()));
		}
	}

	public function actionTracyDelete($name) {
		try {
			if ($name === 'all') {
				if ($handle = opendir($this->logDir)) {
			    	while (false !== ($entry = readdir($handle))) {
				        if ($entry != "." && $entry != ".." && strpos($entry, '.html')) {
				            unlink($this->logDir . $entry);
				        }
				    }
				    closedir($handle);
				}
			} else {
				unlink($this->logDir . $name);
			}
		} catch (\Exception $e) {
			$this->flashMessage('Entries delete error: ' . $e->getMessage(), 'danger');
			$this->redirect('Log:tracy');
		}

		$this->flashMessage('Entry was deleted.', 'success');
		$this->redirect('Log:tracy');
	}

	public function actionDelete($type, $line) {
		$logFile = $this->logDir . $this->logFiles[$type];
		$lineNumbers = null;

		if (strpos($line,'pairs') !== false) {
			$lineNumbers = explode('-', str_replace('pairs-', '', $line));
			arsort($lineNumbers);
		}

		try {
			if (is_numeric($line) or is_array($lineNumbers)){
				$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				
				if (is_array($lineNumbers)){
					foreach($lineNumbers as $key => $lineNumber){
						unset($lines[intval($lineNumber)]);
					}
				} else {
					unset($lines[intval($line)]);
				}
				
				$data = implode("\n", array_values($lines));
				$file = fopen($logFile, 'r+');
				ftruncate($file, 0);
				fwrite($file, $data);
				fclose($file);
			} elseif ($line === 'all') {
				$file = fopen($logFile, 'r+');
				ftruncate($file, 0);
				fclose($file);
			} elseif ($line === 'file') {
				unlink($logFile);
			}
			
		} catch(\Exception $e) {
			$this->flashMessage('Entries delete error: ' . $e->getMessage(), 'danger');
			$this->redirect('Log:log',['type'=>$type]);
		}	

		$this->flashMessage('Entry was deleted.', 'success');
		$this->redirect('Log:log',['type'=>$type]);

	}

	public function actionOut() {
		$this->getUser()->logout();
		$this->redirect('Homepage:default');
	}
}
