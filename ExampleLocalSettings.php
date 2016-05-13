<?php 

# Import the plug-in setup script.
require_once "$IP/extensions/Shibboleth/ShibAuthSetup.php";

# Shibboleth users are stored in the MW database and prohibited from logging
# in using the factory authentication and have empty passwords.
$wgMinimalPasswordLength = 0;

# Last portion of the shibboleth WAYF url for lazy sessions. This value is found
# in your shibboleth.xml file on the setup for your SP WAYF url will look
# something like: /Shibboleth.sso/WAYF/$shib_WAYF.
// $shib_WAYF = "Login";

# If you're using an old style WAYF (Shib 1.3) or new style Discover Service (Shib 2.x)?
# Values are WAYF, DS, or CustomLogin.
// $shib_WAYFStyle = "WAYF";

# If your Shibboleth instance supports TLS.
// $shib_Https = true;

# The text to display in the login link.
// $shib_LoginHint = "Login via Single Sign-on";

# The text to display in the logout link.
// $shib_LogoutHint = "Logout";

# The Assertion Consumer Service URL. On most servers this will be
# "/Shibboleth.sso" but if you don't have such a url you can use the lazy
# session file login.php with some .htaccess rules.
// $shib_ACSU = "/Shibboleth.sso";

# Here is where you should define the mappings between Shibboleth variables and
# MW variables.

# Define the real name mapping. By default it uses the commonName ('cn') variable
# and falls back to concatinating givenName and surName ('sn'). 
if (array_key_exists("cn", $_SERVER)) {
   $shib_RN = $_SERVER['cn'];
} else if (array_key_exists("givenName", $_SERVER) && array_key_exists("sn", $_SERVER)) {
   $shib_RN = ucfirst(strtolower($_SERVER['givenName'])) . ' '
            . ucfirst(strtolower($_SERVER['sn']));
}

# Define the email mapping. By default it uses the mail variable.
$shib_email = isset($_SERVER['mail']) ?  $_SERVER['mail'] : null;

# Collect the list of groups a users is in.
$shib_groups = isset($_SERVER['isMemberOf']) ? $_SERVER['isMemberOf'] : null;

# Define the prefix for groups which are relevant to MW.
// $shib_group_prefix = "wiki.fqdn.example.it";

# Whether Shibboleth should be the sole provider of group membership.
# NOTE: with $shib_group_delete = false, in order to revoke a membership it
# should be deleted both from Shibboleth and user rights management page!
// $shib_group_delete = false;

# Permit the management of user rights.
$wgUserrightsInterwikiDelimiter = '#';

# Define the username mapping. By default it uses the eduPersonPrincipalName
# ('eppn').
$shib_UN = isset($_SERVER['eppn']) ? ucfirst(strtolower($_SERVER['eppn'])) : null;

# The update hook which is called on each user login.
# @param bool $existing: True iff this is an existing user.
# @param User &$user: A reference to the user object.
# Notes:
# * $user->updateUser() is called after the function finishes. You don't have
#   to do it yourself.
# * You have full control of the user object to set, for example email address
#   or the real name.
function ShibUpdateTheUser($existing, &$user) {
	global $shib_email;
	global $shib_RN;

	if (! $existing) {
		if($shib_email != null) {
			$user->setEmail($shib_email);
		}

		if($shib_RN != null) {
			$user->setRealName($shib_RN);
		}
	}

	return true;
}

$wgHooks['ShibUpdateUser'][] = 'ShibUpdateTheUser';

# Hide "IP login" and default login link
# @param Array &$personal_urls A reference to the user's personal links.
# Notes:
# * Comment the first line to keep the factory login link.
# * Comment the second line to keep the anonymous login link.
$wgShowIPinHeader = false;
function NoLoginLinkOnMainPage(&$personal_urls){
	unset($personal_urls['login']);
	unset($personal_urls['anonlogin']);
	return true;
}

$wgHooks['PersonalUrls'][]='NoLoginLinkOnMainPage';

# Disable the factory user login.
# @param Array &$personal_urls a reference to the user's personal links.
# Notes:
# * Comment out the following lines to enable the factory login page.
function disableUserLoginSpecialPage(&$personal_urls) {
	unset($personal_urls['Userlogin']);
	return true;
}

$wgHooks['SpecialPage_initList'][]='disableUserLoginSpecialPage';

# Setup the Shibboleth plug-in.
SetupShibAuth();
