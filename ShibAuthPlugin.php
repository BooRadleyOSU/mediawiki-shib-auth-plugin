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

require_once("$IP/includes/AuthPlugin.php");

/**
 * @class ShibAuthPlugin
 * The Shibboleth subclass of AuthPlugin.
 */
class ShibAuthPlugin extends AuthPlugin {
	var $existingUser = false;
	var $shib_mock = false;

	/**
	 * Check whether there exists a user account with the given name.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username The username to check.
	 * @return bool Whether `$username` exists.
	 * @public
	 */
	function userExists($username) {
		return true;
	}

	/**
	 * Check if a username+password pair is a valid login.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username The username to authenticate against.
	 * @param string $password The given password. Unused.
	 * @return bool Whether the authenticaiton is siccessful.
	 * @public
	 */
	function authenticate($username, $password) {
		global $shib_UN;

		return $username == $shib_UN;
	}

	/**
	 * Modify options in the login template.
	 *
	 * @param UserLoginTemplate $template The login template to modify.
	 * @param string $type Either 'signup' or 'login'.
	 * @public
	 */
	function modifyUITemplate(&$template, &$type) {
		$template->set('usedomain', false);
	}

	/**
	 * Set the domain this plugin is supposed to use when authenticating.
	 *
	 * @param string $domain The desired authentication domain.
	 * @public
	 */
	function setDomain($domain) {
		$this->domain = $domain;
	}

	/**
	 * Check to see if the specific domain is a valid domain. Not meaningful
	 * with Shibboleth.
	 *
	 * @param string $domain The domain to check.
	 * @return bool True iff the domain is valid.
	 * @public
	 */
	function validDomain($domain) {
		return true;
	}

	/**
	 * When a user logs in, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user The user object.
	 * @public
	 */
	function updateUser(&$user) {
		wfRunHooks('ShibUpdateUser', array($this->existingUser, &$user));

		$user->setOption('rememberpassword', 0);
		$user->saveSettings();
		return true;
	}

	/**
	 * Return true if the wiki should create a new local account automatically
	 * when asked to login a user who doesn't exist locally but does in the
	 * external auth database.
	 *
	 * If you don't automatically create accounts, you must still create
	 * accounts in some way. It's not possible to authenticate without
	 * a local account.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool Whether MW should automatically create users.
	 * @public
	 */
	function autoCreate() {
		return true;
	}

	/**
	 * Start pretending to allow password changes. This feature is needed for
	 * the AutoAuth hook.
	 */
	function enableMockPasswords() {
		$this->shib_mock = true;
	}

	/**
	 * Stop pretending to allow password changes. This feature is needed for the
	 * AutoAuth hook.
	 */
	function disableMockPasswords() {
		$this->shib_mock = false;
	}

	/**
	 * Return true if the authentication backend supports password changes. The
	 * global variable `$shib_mock` can be set in order to make this plugin
	 * pretend that password changes are possible.
	 *
	 * @return bool
	 */
	function allowPasswordChange() {
		return $this->shib_mock;
	}

	/**
	 * Set the given password in the authentication database.
	 * Return true if successful.
	 * The global variable `$shib_mock` can be set in order to make this
	 * plugin pretend that password changes are possible.
	 *
	 * @param User $user The user to modify.
	 * @param string $password The new password.
	 * @return bool True iff the password change was successful.
	 * @public
	 */
	function setPassword($user, $password) {
		return $this->shib_mock;
	}

	/**
	 * Update user information in the external authentication database.
	 * Return true if successful. Not meaningful with Shibboleth.
	 *
	 * @param User $user The user to modify.
	 * @return bool True iff the update was successful.
	 * @public
	 */
	function updateExternalDB($user) {
		return true;
	}

	/**
	 * Check to see if external accounts can be created.
	 * Return true if external accounts can be created.
	 * This function is not meaningful with Shibboleth.
	 *
	 * @return bool True when external accounts can be created.
	 * @public
	 */
	function canCreateAccounts() {
		return false;
	}

	/**
	 * Add a user to the external authentication database.
	 * Return true if successful.
	 * This function is not meaningful with Shibboleth.
	 *
	 * @param User $user The user to add.
	 * @param string $password The user's password.
	 * @param string $email  The user's email.
	 * @param string $realname The user's real name.
	 * @return bool True iff the user add was successful.
	 */
	function addUser($user, $password, $email, $realname) {
		return false;
	}

	/**
	 * Return true to prevent logins that don't authenticate here from being
	 * checked against the local database's password fields.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool True if authenticaion should not fall back to factory.
	 * @public
	 */
	function strict() {
		return false;
	}

	/**
	 * When creating a user account, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user User object.
	 * @param bool $autocreate True if user is being autocreated on login.
	 */
	function initUser(&$user, $autocreate) {
		$this->updateUser($user);
	}

	/**
	 * Transforms the given uesrname into its canonical form.
	 * @param string $username The username to canonicalize.
	 */
	function getCanonicalName($username) {
		return $username;
	}
}
?>
