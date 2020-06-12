<?php

/**
 * @package     OneSignal Plugin
 *
 * @copyright   Copyright (C) 2020. All rights reserved
 * @license     MIT License; see LICENSE
 */

defined('_JEXEC') or die;
// Import lib
use Joomla\CMS\Log\Log;

/**
 * Plugin push notifications to OneSignal
 */
class plgContentOneSignalPlugin extends JPlugin
{
    public function onContentAfterSave($context, $article, $isNew)
    {
        // Only when creating a new article
		Log::add('OneSignal Plugin activated', Log::INFO, 'onesignal-plugin');
        if (isset($context) && ( $context == "com_content.article" || $context == "com_content.form") && $isNew) {
			Log::add('New article detected', Log::INFO, 'onesignal-plugin');
            $categories = $this->params->get('categories', '');
            $featured = $this->params->get('featured', 1);
            if ($article->featured >= $featured && ($categories == '' || (isset($article->catid) && in_array($article->catid, $categories)))) {
				Log::add('Notification will be generated', Log::INFO, 'onesignal-plugin');
                $this->sendPushNotification($article->title, $this->getLinkToArticle($article));
				Log::add('Notification generated', Log::INFO, 'onesignal-plugin');
            }
        }
    }
    private function sendPushNotification($article_title, $article_link)
    {
        // Get the settings
        $onesignal_app_id = $this->params->get('oneSignalAppId', '');
        $onesignal_rest_key = $this->params->get('oneSignalRestKey', '');
        $message_title = $this->params->get('messageTitle', 'New article');
        $segments = $this->params->get('segments', 'Subscribed Users');
        $language = $this->params->get('language', 'en');

        // API endpoint
        $url = 'https://onesignal.com/api/v1/notifications';

        // Header with basic authentication
        $header = array("Content-Type: application/json; charset=utf-8", 'Authorization: Basic ' . $onesignal_rest_key);
        // Notification's heading & content 
        $headings = array($language => $message_title);
        $contents = array($language => $article_title);
        // Segments
        $segments = explode(',', $segments);

        // HTTP request data
        $data = array('app_id' => $onesignal_app_id, 'headings' => $headings, 'contents' => $contents, 'included_segments' => $segments, 'url' => $article_link);
        $options = array(
            'http' => array(
                'header'  => $header,
                'method'  => 'POST',
                'content' => json_encode($data)
            )
        );
		
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
		
        return $result;
    }
    private function getLinkToArticle($article)
    {
        return rtrim(JUri::root(), '/') . '/index.php?option=com_content&view=article&id=' . $article->id;
    }
}
