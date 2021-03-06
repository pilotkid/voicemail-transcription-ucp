<?php
namespace FreePBX\modules\Voicemail;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$dirs = [];
		$vmx = [];
		$voiceMail = $this->FreePBX->Voicemail;
		$voicemailConf = $voiceMail->getVoicemail(false);
		$backupsettings = $voiceMail->getBackupSettings($id);
		$mailboxData = $voiceMail->bulkhandlerExport('extensions');
		foreach($backupsettings as $exten){
			$vmx[$exten['extension']] = $voiceMail->Vmx->vmxexport($exten['extension']);
			if(!$exten['egreetings']){
				$greetings = $voiceMail->getGreetingsByExtension($exten['extension']);
				foreach($greetings as $greeting){
					$path = pathinfo($greeting,PATHINFO_DIRNAME);
					$dirs[] = $path;
					$this->addFile(basename($greeting), $path, 'ASTVARSPOOLDIR', "greeting");
				}
			}
			if(!$exten['emessages']){
				$fileDirList = $voiceMail->allFileList($exten['extension']);
				foreach($fileDirList['dirs'] as $dir){
					$dirs[] = $dir;
				}
				foreach ($fileDirList['files'] as $file) {
					if($file['basename'] === 'busy.wav' || $file['basename'] === 'unavail.wav'){
						continue;
					}
					$this->addFile($file['basename'], $file['path'], $file['base'], $file['type']);
				}
			}
			if($exten['rpassword'] && isset($mailboxData[$exten['extension']]['voicemail_vmpwd'])){
				$mailboxData[$exten['extension']]['voicemail_vmpwd'] = $exten['extension'];
			}
		}

		$configs = [
			'voicemailConf' => $voicemailConf,
			'mailboxData' => $mailboxData,
			'features' => $this->dumpFeatureCodes(),
			'settings' => $this->dumpAdvancedSettings(),
			'tables' => $this->dumpDBTables('voicemail_admin',false)
		];
		$file = $this->FreePBX->Config->get('ASTETCDIR')."/voicemail.conf";
		$path = pathinfo($file,PATHINFO_DIRNAME);
		$this->addFile(basename($file), $path, 'ASTETCDIR', "conf");
		$this->addDirectories($dirs);
		$this->addDependency('core');
		$this->addConfigs($configs);
	}
}
