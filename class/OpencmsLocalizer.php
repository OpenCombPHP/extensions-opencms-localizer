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
		OpencmsLocalizer::createTable(null);
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
}