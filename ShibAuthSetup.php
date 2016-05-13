<?php

/**
 * Portions Copyright 2006, 2007 Regents of the University of California.
 * Portions Copyright 2007, 2008 Steven Langenaken
 * Portions Copyright 2016 Joseph Chagnon
 *
 * Released under the GNU General Public License
 *
 * Extension Maintainers:
 *   * Steven Langenaken - assertion support, robust https checking,
 *     lazy auth bugfixes, ShibUpdateUser hook
 *   * Balazs Varga - bugfixes, customizations
 * Extension Developers:
 *   * D.J. Capelis - Developed initial version of the extension
 */

$wgExtensionFunctions[] = 'SetupShibAuth';
$wgExtensionCredits['other'][] = array(
	'name' => 'Shibboleth Authentication',
	'version' => '1.3.0',
	'author' => "D.J. Capelis",
	'url' => "https://github.com/BooRadleyOSU/mediawiki-shib-auth-plugin",
	'description' => "Shibboleth Authentication Plug-in.",
);

require_once("ShibAuthPlugin.php");

/**
 * Return the name of the Auth hook. They changed the name in newer versions
 * of MW.
 * @return string The name of the AutoAuth hook.
 */
function ShibGetAuthHook() {
	global $wgVersion;
	return $wgVersion >= "1.13" ? 'UserLoadFromSession' : 'AutoAuthenticate';
}

/**
 * The entry point into the extension. After configuration this funciton should
 * be called to enable the plugin.
 * @todo The login link is incorrectly removed on the logout page.
 */
function SetupShibAuth()
{
	global $shib_UN;
	global $wgHooks;
	global $wgAuth, $wgUser;
	global $wgCookieExpiration;

	if($shib_UN != null){
		// Add the active links when a Shib user is logged in.
		$wgCookieExpiration = -3600;
		$wgHooks[ShibGetAuthHook()][] = "Shib".ShibGetAuthHook();
		$wgHooks['PersonalUrls'][] = 'ShibActive';
		$wgAuth = new ShibAuthPlugin();
	} elseif ($wgUser != null && $wgUser->isLoggedIn()) {
		// Remove the login link if a MW user is logged in.
		$wgHooks['PersonalUrls'] = array_diff($wgHooks['PersonalUrls'], array('ShibLinkAdd'));
	} else {
		// Add the login link when nobody is logged in.
		$wgHooks['PersonalUrls'][] = 'ShibLinkAdd';
	}
}

/**
 * Adds the Shib login link to the user's personal links.
 * @param dict &$personal_urls The user's personal links. 
 * @param $title The title of the page.
 * @return bool True if the modification is successful.
 */
function ShibLinkAdd(&$personal_urls, $title)
{
	global $shib_WAYF, $shib_login_hint, $shib_https;
	global $shib_WAYF;

	// Set default values.
	if (! isset($shib_https)) {
		$shib_https = false;
	}

	if (! isset($shib_WAYF)) {
		$shib_WAYF = 'WAYF';
	}

	if ($shib_WAYF == 'WAYF') {
		$shib_consumer_prefix = 'WAYF/';
	} else {
		$shib_consumer_prefix = '';
	}

	if (! isset($shib_login_hint)) {
		$shib_login_hint = "Login via Single Sign-on";
	}

	// Get the current page.
	$pageurl = $title->getLocalUrl();

	// Generate the login link and add it to the user's personal urls.
	if ($shib_WAYF == "Login") {
		$personal_urls['SSOlogin'] = array(
			'text' => $shib_login_hint,
			'href' => ($shib_https ? 'https' :  'http') .'://' . $_SERVER['HTTP_HOST'] .
			getShibACSU() . "/" . $shib_consumer_prefix . $shib_WAYF .
			'?target=' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
			'://' . $_SERVER['HTTP_HOST'] . $pageurl
		);
	}
	elseif ($shib_WAYF == "CustomLogin") {
		$personal_urls['SSOlogin'] = array(
			'text' => $shib_login_hint,
			'href' => ($shib_https ? 'https' :  'http') .'://' . $_SERVER['HTTP_HOST'] .
			getShibACSU() .
			'?target=' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
			'://' . $_SERVER['HTTP_HOST'] . $pageurl
		);
	}
	else {
		$personal_urls['SSOlogin'] = array(
			'text' => $shib_login_hint,
			'href' => ($shib_https ? 'https' :  'http') .'://' . $_SERVER['HTTP_HOST'] .
			getShibACSU() . "/" . $shib_consumer_prefix . $shib_WAYF .
			'?target=' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
			'://' . $_SERVER['HTTP_HOST'] . $pageurl
		);
	}

	return true;
}

/**
 * Add the userpage and logout links to the user's personal links.
 * @param dict &$personal_urls 
 * @param obj $title
 * @return bool True when the modifications are successful.
 */
function ShibActive(&$personal_urls, $title)
{
	global $shib_logout_hint, $shib_https;
	global $shib_RN;
	global $shib_map_info;
	global $shib_logout;
	global $wgScriptPath;

	// Set default values
	if (!isset($shib_logout_hint) || $shib_logout_hint == '') {
		$shib_logout_hint = "Logout";
	}

	$personal_urls['logout'] = array(
		'text' => $shib_logout_hint, 
		'href' => ($shib_https ? 'https' : 'http') .'://' . $_SERVER['HTTP_HOST'] .
		(isset($shib_logout) ? $shib_logout : getShibACSU() . "/Logout") .
		'?return=' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
		'://'. $_SERVER['HTTP_HOST']. "$wgScriptPath/index.php?title=Special:UserLogout&amp;returnto=" .
		$title->getPartialURL()
	);

	if ($shib_RN && $shib_map_info) {
		$personal_urls['userpage']['text'] = $shib_RN;
	}

	return true;
}

/**
 * Returns the Shibboleth AssertionConsumerServiceURL
 * @todo Is this function really necessary?
 * @return string The Shib SSO service URL.
 */
function getShibACSU() {
	global $shib_ACSU;

	// Set default value
	if (! isset($shib_ACSU) || $shib_ACSU == '') {
		$shib_ACSU = "/Shibboleth.sso";
	}

	return $shib_ACSU;
}

/**
 * The Shib implementation of AutoAuth.
 * @param Uesr &$user The user to authenticate.
 */
function ShibAutoAuthenticate(&$user) {
	ShibUserLoadFromSession($user, true);
}

/**
 * The Shib implementation of AutoAuth.
 * @todo Document magic.
 * @param User $user The user to load.
 * @param object &$result The result of the load. Unused.
 * @return bool True iff the load is successful.
 */
function ShibUserLoadFromSession($user, &$result) {
	global $IP;
	global $wgContLang;
	global $wgAuth;
	global $shib_UN;
	global $wgHooks;
	global $shib_map_info;
	global $shib_map_info_existing;
	global $shib_groups;

	/**
	 * MW can be configured to require that article titles begin with a
	 * capital letter. Since each user has an associated article usernames must
	 * then follow the same conventions.
	 * @todo Can we make use of the cannonicalization in ShibAuthPlugin?
	 */
	$shib_UN = Title::makeTitleSafe( NS_USER, $shib_UN);
	$shib_UN = $shib_UN->getText();

	/** @bug Some versions of MW call AutoAuth with null users */
	if ($user === null) {
		$user = User::loadFromSession();
	}

	// Short circut if the user is already logged in.
	if($user->isLoggedIn()) {
		return true;
	}

	// Has the user already been created?
	if (User::idFromName($shib_UN) != null && User::idFromName($shib_UN) != 0) {
		$user = User::newFromName($shib_UN);
		$user->load();
		$wgAuth->existingUser = true;
		// Ensure that the password in the local db is nologin.
		$wgAuth->updateUser($user);
		wfSetupSession();
		$user->setCookies();
		ShibAddGroups($user);
		return true;
	}

	// Start the user creation process.
	$user->setName($wgContLang->ucfirst($shib_UN));

	/*
	 * Since the AuthPlugin is only called when someone is being logged in, if
	 * they aren't then we need to force it. The way MW does this is using a
	 * loginForm that performs all the needed functions.
	 */
	require_once("$IP/includes/specials/SpecialUserlogin.php");

	// Begin the black magic.
	global $wgLang;
	global $wgContLang;
	global $wgRequest;
	$wgLangUnset = false;

	if (!isset($wgLang)) {
		$wgLang = $wgContLang;
		$wgLangUnset = true;
	}

	// This creates the login form that will do the user creation.
	$lf = new LoginForm($wgRequest);

	// Clean up the hack.
	if ($wgLangUnset == true) {
		unset($wgLang);
		unset($wgLangUnset);
	}

	// New versions of MW fail when passwords aren't able to be set.
	$wgAuth->enableMockPasswords();

	// Now we _do_ the black magic.
	$user->loadDefaults($shib_UN);
	$lf->initUser($user, true);

	$wgAuth->disableMockPasswords();
	// End the black magic.

	$user->saveSettings();
	wfSetupSession();
	$user->setCookies();
	ShibAddGroups($user);

	return true;
}

/**
 * Parses a user's Shib groups and adds groups which start with a prefix.
 * @param User $user The current user.
 */
function ShibAddGroups($user) {
	global $shib_groups;
	global $shib_group_prefix;
	global $shib_group_delete;

	if (isset($shib_group_delete) && $shib_group_delete) {
		$oldGroups = $user->getGroups();
		foreach ($oldGroups as $group) {
			$user->removeGroup($group);
		}
	}

	if (isset($shib_groups)) {
		foreach (explode(';', $shib_groups) as $group) {
			if (isset($shib_group_prefix) && !empty($shib_group_prefix)) {
				$vals = explode(":", $group);
				if ($vals[0] == $shib_group_prefix) {
					$user->addGroup($vals[1]);
				}
			}
			else {
				$user->addGroup($group);
			}
		}
	}
}
?>
