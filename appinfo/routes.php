<?php
/**
 * Nextcloud - Wire
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

return [
	'routes' => [
		['name' => 'config#isUserConnected', 'url' => '/is-connected', 'verb' => 'GET'],
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],

		['name' => 'wireAPI#sendMessage', 'url' => '/sendMessage', 'verb' => 'POST'],
		['name' => 'wireAPI#sendLinks', 'url' => '/sendLinks', 'verb' => 'POST'],
		['name' => 'wireAPI#sendFile', 'url' => '/sendFile', 'verb' => 'POST'],
		['name' => 'wireAPI#getConversations', 'url' => '/conversations', 'verb' => 'GET'],
		['name' => 'wireAPI#getWireUrl', 'url' => '/url', 'verb' => 'GET'],
		['name' => 'wireAPI#getUserAvatar', 'url' => '/users/{userId}/image', 'verb' => 'GET'],
		['name' => 'wireAPI#getTeamAvatar', 'url' => '/teams/{teamId}/image', 'verb' => 'GET'],

		['name' => 'files#getFileImage', 'url' => '/preview', 'verb' => 'GET'],
	]
];
