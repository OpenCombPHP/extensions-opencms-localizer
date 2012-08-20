<?php 
namespace org\opencomb\opencmslocalizer ;

use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\mvc\view\widget\Menu;
use org\jecat\framework\locale\LanguagePackageFolders;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\locale\Locale;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\ObjectContainer ;
use org\jecat\framework\ui\xhtml\Node ;
use org\jecat\framework\db\sql\compiler\NameMapper; 
use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\model\db\orm\Prototype;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\util\EventManager;
use org\opencomb\localizer\LangSwich;
use org\opencomb\localizer\LangSelectDefault;
use org\opencomb\coresystem\mvc\controller\ControlPanel ;
use org\jecat\framework\setting\Setting ;

class OpencmsLocalizer extends Extension 
{
	public function load()
	{	
		BeanFactory::singleton()->registerBeanClass("org\\opencomb\\localizer\\LangSelect",'langselect') ;
		ControlPanel::registerMenuHandler( array(__CLASS__,'buildControlPanelMenu') ) ;
		OpencmsLocalizer::createTable(null);
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		// 合并配置数组，增加菜单
		$arrConfig['item']['system']['item']['platform-manage']['item']['opencmslocalizer'] = array(
				'title'=> 'CMS管理本地化' ,
		);
	}

	public function active()
	{	
		$aLocale = Locale::singleton();
		$sPrefix = DB::singleton()->tableNamePrefix();
		$sSQLArticle = "show tables like"."\"".$sPrefix."opencms_article".'_'.str_replace('-', '_', $aLocale->localeName())."\"";
		$sSQLAttachment = "show tables like"."\"".$sPrefix."opencms_attachment".'_'.str_replace('-', '_', $aLocale->localeName())."\"";
		$sSQLCategory = "show tables like"."\"".$sPrefix."opencms_category".'_'.str_replace('-', '_', $aLocale->localeName())."\"";
		
		$aRecordsArticle = DB::singleton()->query($sSQLArticle);
		$aRecordsAttachment = DB::singleton()->query($sSQLAttachment);
		$aRecordsCategory = DB::singleton()->query($sSQLCategory);
		
		//数据表映射
		NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		
	}
	
	//注册
	public function initRegisterEvent(EventManager $aEventMgr)
	{
		$aEventMgr->registerEventHandle(
				'org\\opencomb\\localizer\\LangSwich'
				, LangSwich::swichLang
				, array(__CLASS__,'swichLangdd')
		)
			->registerEventHandle(
				'org\\opencomb\\localizer\\LangSelectDefault'
				, LangSelectDefault::selectDefault
				, array(__CLASS__,'selectDefault')
		);
	}
	
	//切换语言
	static public function swichLangdd($sLangCountryNew,$sLangCountryOld,$sPageUrl)
	{
		OpencmsLocalizer::createTable($sLangCountryNew);
		OpencmsLocalizer::switchSetting($sLangCountryNew,$sLangCountryOld,$sPageUrl);
	}
	
	//选择默认语言
	static public function selectDefault($sLangCountryNew)
	{
		OpencmsLocalizer::createTable($sLangCountryNew);
	}
	
	//创建数据表
	static function createTable($sLangCountryNew)
	{	
		if(!isset($sLangCountryNew))
		{
			$aLocale = Locale::singleton();
			$sLangCountryNew = str_replace('-', '_', $aLocale->localeName());
		}else{
			
			$sLangCountryNew = str_replace('-', '_', $sLangCountryNew);
		}

		$sPrefix = DB::singleton()->tableNamePrefix();
		$sSQLArticle = "show tables like"."\"".$sPrefix."opencms_article".'_'.$sLangCountryNew."\"";
		$sSQLAttachment = "show tables like"."\"".$sPrefix."opencms_attachment".'_'.$sLangCountryNew."\"";
		$sSQLCategory = "show tables like"."\"".$sPrefix."opencms_category".'_'.$sLangCountryNew."\"";
		
		$aRecordsArticle = DB::singleton()->query($sSQLArticle);
		$aRecordsAttachment = DB::singleton()->query($sSQLAttachment);
		$aRecordsCategory = DB::singleton()->query($sSQLCategory);
		
		$sSQLT = "show create table" . ' ' . $sPrefix . "opencms_article";
		$aRecords = DB::singleton()->query($sSQLT);
		$arrCreateCommandT = $aRecords->fetchAll();
		
		//文章表
		if(!count($aRecordsArticle->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_article";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($arrCreateCommand[0]['Table'], $sPrefix.'opencms_article'.'_'.$sLangCountryNew, $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
		}
		
		//附件表
		if(!count($aRecordsAttachment->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_attachment";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($arrCreateCommand[0]['Table'], $sPrefix.'opencms_attachment'.'_'.$sLangCountryNew, $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
		}
		
		//分类表
		if(!count( $aRecordsCategory->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_category";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($arrCreateCommand[0]['Table'], $sPrefix.'opencms_category'.'_'.$sLangCountryNew, $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
		}
	}
	
	static private function switchSetting($sLangCountryNew,$sLangCountryOld,$sPageUrl){
		// export old
		$aSetting = Extension::flyweight('opencms')->setting();
		
		$arrExportKeys = array('menu','index');
		$arrSettingExport = array();
		foreach($arrExportKeys as $sExportKey){
			$arrSettingExport[ 'k:'.$sExportKey ] = self::exportSetting($aSetting,'/'.$sExportKey.'/');
		}
		
		// save old
		$aOlSetting = Extension::flyweight('opencms-localizer')->setting();
		$aOlSetting->setItem(
			'/switchSetting',
			$sLangCountryOld,
			'return '.var_export($arrSettingExport,true).';'
		);
		
		// remove old
		foreach($arrExportKeys as $sExportKey){
			$aSetting->deleteKey('/'.$sExportKey,'/');
		}
		
		// read new
		$arrSettingImport = eval($aOlSetting->item(
			'/switchSetting',
			$sLangCountryNew,
			'return array();'
		));
		
		// import new
		self::importSetting($aSetting,$arrSettingImport,'/');
	}
	
	static private function exportSetting(Setting $aSetting,$sPath){
		$arrRtn = array() ;
		foreach($aSetting->keyIterator($sPath) as $aKey){
			$arrRtn[ 'k:'.$aKey->name() ] = self::exportSetting($aSetting,$sPath.$aKey->name().'/');
		}
		foreach($aSetting->itemIterator($sPath) as $sItem){
			$arrRtn[ $sItem ] = $aSetting->item($sPath,$sItem,null);
		}
		return $arrRtn ;
	}
	
	static private function importSetting(Setting $aSetting,array $arrSettingImport , $sPath){
		foreach($arrSettingImport as $sKey => $aValue){
			$sPrefix = substr($sKey,0,2);
			$sOriKey = substr($sKey,2);
			if( 'k:' === $sPrefix ){
				self::importSetting($aSetting,$aValue,$sPath.$sOriKey.'/');
			}else{
				$aSetting->setItem(
					$sPath,
					$sKey,
					$aValue
				);
			}
		}
	}
}
