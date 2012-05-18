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

class OpencmsLocalizer extends Extension 
{
	public function load()
	{
		BeanFactory::singleton()->registerBeanClass("org\\opencomb\\localizer\\LangSelect",'langselect') ;
		Menu::registerBuildHandle(
				'org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame'
				, 'frameView'
				, 'mainMenu'
				, array(__CLASS__,'buildControlPanelMenu')
		) ;
		// 注册语言包目录
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		// 合并配置数组，增加菜单
		$arrConfig['item:system']['item:platform-manage']['item:opencmslocalizer'] = array(
				'title'=> 'CMS管理本地化' ,
				'link' => '?c=org.opencomb.opencmslocalizer.OpencmsLocalizer' ,
				'query' => 'c=org.opencomb.opencmslocalizer.OpencmsLocalizer' ,
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
		
		//文章表
		if(!count($aRecordsArticle->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_article";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_article', $sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}else{
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
			
		}
		
		//附件表
		if(!count($aRecordsAttachment->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_attachment";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_attachment', $sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}else{
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
					
		}
		
		//分类表
		if(!count( $aRecordsCategory->fetchAll()))
		{
			$sSQL = "show create table" . ' ' . $sPrefix . "opencms_category";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_category', $sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}else{
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
					
		}
		/*
		var_dump($arrCreateCommand);
		exit;
		try{
			
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName());
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table" . $sPrefix . "opencms_article";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_article', $sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
	
		try{
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName());
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table opencms_attachment";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_attachment', $sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
		try{
		
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName());
		
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table opencms_category";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_category', $sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
		*/
		
	}
	

	
	static public function onBeforeRespond($sLangCountryNew,$sLangCountryOld,$sPageUrl)
	{
		echo $sPageUrl;exit;
		$arrLangCountry = explode('_',$sLangCountryNew);
		$sDpath = $sLangCountryNew;
		$arrLang =OpencmsLocalizer::langIterator();
		$arrLang[$sDpath]['selected']=1;
		
		foreach($arrLang as $key=>$value)
		{
			if($key!=$sDpath)
			{
				$arrLang[$key]['selected']=0;
			}else{
				$arrLang[$key]['selected']=1;
			}
		
		}
		
		$aSetting = Extension::flyweight('localizer')->setting();
		$aSetting->deleteKey('/');
		foreach($arrLang as $key=>$value)
		{
			$aSetting->setItem('/',$key,$value);
		}
		
		Locale::switchSessionLocale($arrLangCountry[0],$arrLangCountry[1],true);	
		
	}
	
	static function langIterator(){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLang[$value]=$aKey->item($value,array());
		}
		return $arrLang;
	}
}