<?php
namespace Podlove\Modules\ImportExport;

use Podlove\Model;

class Exporter {

	const XML_NAMESPACE = 'http://podlove.org/podlove-podcast-publisher/export';

	public function __construct() {
		add_action('podlove_xml_export', array($this, 'exportEpisodes'));
		add_action('podlove_xml_export', array($this, 'exportAssets'));
		add_action('podlove_xml_export', array($this, 'exportFeeds'));
		add_action('podlove_xml_export', array($this, 'exportFileType'));
		add_action('podlove_xml_export', array($this, 'exportMediaFile'));
		add_action('podlove_xml_export', array($this, 'exportTemplates'));
		add_action('podlove_xml_export', array($this, 'exportOptions'));
	}

	public function download() {
		$this->setDownloadHeaders();
		echo $this->getXml();
		exit;
	}

	public function exportEpisodes(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'episodes', 'episode', '\Podlove\Model\Episode');
	}

	public function exportAssets(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'assets', 'asset', '\Podlove\Model\EpisodeAsset');
	}

	public function exportFeeds(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'feeds', 'feed', '\Podlove\Model\Feed');
	}

	public function exportFileType(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'filetypes', 'filetype', '\Podlove\Model\FileType');
	}

	public function exportMediaFile(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'mediafiles', 'mediafile', '\Podlove\Model\MediaFile');
	}

	public function exportTemplates(\SimpleXMLElement $xml) {
		self::exportTable($xml, 'templates', 'template', '\Podlove\Model\Template');
	}

	public function exportOptions(\SimpleXMLElement $xml)
	{
		$options = array(
			'podlove',
			'podlove_active_modules',
			'podlove_asset_assignment',
			'podlove_metadata',
			'podlove_podcast',
			'podlove_template_assignment',
			'podlove_webplayer_formats',
			'podlove_webplayer_settings',
			'podlove_contributors'
		);

		$xml_group = $xml->addChild('xmlns:wpe:options');
		foreach ($options as $option_name) {
			$value = get_option($option_name);
			if ($value !== false) {
				if (is_array($value)) {
					$xml_group->addChild("xmlns:wpe:$option_name", serialize($value));
				} else {
					$xml_group->addChild("xmlns:wpe:$option_name", $value);
				}
			}
		}
	}

	public static function exportTable(\SimpleXMLElement $xml, $group_name, $item_name, $table_class)
	{
		$xml_group = $xml->addChild("xmlns:wpe:$group_name");
		foreach ($table_class::all() as $mediafile) {
			$xml_item = $xml_group->addChild("xmlns:wpe:$item_name");
			foreach ($table_class::property_names() as $property_name) {
				
				if (strlen($mediafile->$property_name) === 0)
					continue;

				// This weird syntax is intentional. It is the only way to make
				// SimpleXML escape ampersands. 
				// See http://stackoverflow.com/a/12640393/72448
				$xml_item->addChild("xmlns:wpe:$property_name")->{0} = $mediafile->$property_name;
			}
		}
	}

	private function getDownloadFileName()
	{
		$sitename = sanitize_key(get_bloginfo('name'));
		
		if (!empty($sitename))
			$sitename .= '.';

		return $sitename . 'podlove.' . date( 'Y-m-d' ) . '.xml';
	}

	private function setDownloadHeaders() {		
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->getDownloadFileName() );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		header( 'Cache-control: private' );
		header( 'Expires: -1' );
	}

	public function getXml() {
		$xml = new \SimpleXMLElement('<wpe:export/>');
		// Double xmlns looks strange but is intentionally/required.
		// See http://stackoverflow.com/a/9391673/72448
		$xml->addAttribute('xmlns:xmlns:wpe', self::XML_NAMESPACE);
		$xml->addAttribute('version', '1.0');

		// add comments
		$comment = "\n\tPublisher Version: " . \Podlove\get_plugin_header( 'Version' );
		$comment.= "\n\tExport Date: " . date('r');
		$comment.= "\n\t";

		$dom = dom_import_simplexml($xml);
		$commentElement = $dom->ownerDocument->createComment($comment);
		$dom->appendChild($commentElement);

		do_action('podlove_xml_export', $xml);

		// return formatted
		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->formatOutput = true;
		return $dom->saveXML();
	}
}