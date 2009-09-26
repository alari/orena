<?php
require_once "Auth/OpenID/Discover.php";
abstract class O_OpenId_Provider_Command extends O_Command {

/**
 * Build a URL to a server action
 *
 * XXX invent logic to underastand current action
 */
protected function buildURL($action=null, $escaped=true)
{
    $url = "http://".O_Registry::get("app/env/http_host");
    if ($action) {
        $url .= '/' . $action;
    }
    return $escaped ? htmlspecialchars($url, ENT_QUOTES) : $url;
}

/**
 * Handle a standard OpenID server request
 */
function action_default()
{
    header('X-XRDS-Location: '.$this->buildUrl("idpXrds"));

    $server = $this->getServer();
    $request = $server->decodeRequest();

    if (!$request) {
    	//XXX do we need render about? redirect to identity
        return about_render();
    }

    $this->setRequestInfo($request);

    if (in_array($request->mode,
                 array('checkid_immediate', 'checkid_setup'))) {

        if ($request->idSelect()) {
            // Perform IDP-driven identifier selection
            if ($request->mode == 'checkid_immediate') {
                $response = $request->answer(false);
            } else {
            	// XXX call trust template
                return trust_render($request);
            }
        } else if ((!$request->identity) &&
                   (!$request->idSelect())) {
            // No identifier used or desired; display a page saying
            // so.
            // XXX how it could be?
            return noIdentifier_render();
        } else if ($request->immediate) {
            $response =& $request->answer(false, buildURL());
        } else {
            if (!getLoggedInUser()) {
            	// Redirect to login page
                return login_render();
            }
            // XXX call trust template; what's the difference with :44?
            return trust_render($request);
        }
    } else {
        $response = $server->handleRequest($request);
    }

    $webresponse = $server->encodeResponse($response);

    if ($webresponse->code != AUTH_OPENID_HTTP_OK) {
        header(sprintf("HTTP/1.1 %d ", $webresponse->code),
               true, $webresponse->code);
    }

    foreach ($webresponse->headers as $k => $v) {
        header("$k: $v");
    }

    header(header_connection_close);
    print $webresponse->body;
    exit(0);
}

/**
 * Log out the currently logged in user
 */
function action_logout()
{
    setLoggedInUser(null);
    setRequestInfo(null);
    return authCancel(null);
}

/**
 * Check the input values for a login request
 */
function login_checkInput($input)
{
    $openid_url = false;
    $errors = array();

    if (!isset($input['openid_url'])) {
        $errors[] = 'Enter an OpenID URL to continue';
    }
    if (count($errors) == 0) {
        $openid_url = $input['openid_url'];
    }
    return array($errors, $openid_url);
}

/**
 * Log in a user and potentially continue the requested identity approval
 */
function action_login()
{
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
    case 'GET':
        return login_render();
    case 'POST':
        $info = getRequestInfo();
        $fields = $_POST;
        if (isset($fields['cancel'])) {
            return authCancel($info);
        }

        list ($errors, $openid_url) = login_checkInput($fields);
        if (count($errors) || !$openid_url) {
            $needed = $info ? $info->identity : false;
            return login_render($errors, @$fields['openid_url'], $needed);
        } else {
            setLoggedInUser($openid_url);
            return doAuth($info);
        }
    default:
        return login_render(array('Unsupported HTTP method: $method'));
    }
}

/**
 * Ask the user whether he wants to trust this site
 */
function action_trust()
{
    $info = getRequestInfo();
    $trusted = isset($_POST['trust']);
    return $this->doAuth($info, $trusted, true, @$_POST['idSelect']);
}

function action_idpage()
{
    $identity = $_GET['user'];
    return idpage_render($identity);
}

function action_idpXrds()
{
    return idpXrds_render();
}

function action_userXrds()
{
    $identity = $_GET['user'];
    return userXrds_render($identity);
}














/**
 * Get the URL of the current script
 */
function getServerURL()
{
    $path = $_SERVER['SCRIPT_NAME'];
    $host = $_SERVER['HTTP_HOST'];
    $port = $_SERVER['SERVER_PORT'];
    $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
    if (($s && $port == "443") || (!$s && $port == "80")) {
        $p = '';
    } else {
        $p = ':' . $port;
    }

    return "http$s://$host$p$path";
}



/**
 * Extract the current action from the request
 */
function getAction()
{
    $path_info = @$_SERVER['PATH_INFO'];
    $action = ($path_info) ? substr($path_info, 1) : '';
    $function_name = 'action_' . $action;
    return $function_name;
}

/**
 * Write the response to the request
 */
function writeResponse($resp)
{
    list ($headers, $body) = $resp;
    array_walk($headers, 'header');
    header(header_connection_close);
    print $body;
}

/**
 * Instantiate a new OpenID server object
 *
 * @return O_OpenId_Provider
 */
protected function getServer()
{
	return O_OpenId_Provider::getInstance(O_OpenId_Storage::getInstance(), $this->buildURL());
}

/**
 * Return a hashed form of the user's password
 */
function hashPassword($password)
{
    return bin2hex(Auth_OpenID_SHA1($password));
}

/**
 * Get the openid_url out of the cookie
 *
 * @return mixed $openid_url The URL that was stored in the cookie or
 * false if there is none present or if the cookie is bad.
 */
function getLoggedInUser()
{
    return isset($_SESSION['openid_url'])
        ? $_SESSION['openid_url']
        : false;
}

/**
 * Set the openid_url in the cookie
 *
 * @param mixed $identity_url The URL to set. If set to null, the
 * value will be unset.
 */
function setLoggedInUser($identity_url=null)
{
    if (!isset($identity_url)) {
        unset($_SESSION['openid_url']);
    } else {
        $_SESSION['openid_url'] = $identity_url;
    }
}

private function getRequestInfo()
{
    return isset($_SESSION['request'])
        ? unserialize($_SESSION['request'])
        : false;
}

private function setRequestInfo($info=null)
{
    if (!isset($info)) {
        unset($_SESSION['request']);
    } else {
        $_SESSION['request'] = serialize($info);
    }
}


function getSreg($identity)
{
    // from config.php
    global $openid_sreg;

    if (!is_array($openid_sreg)) {
        return null;
    }

    return $openid_sreg[$identity];

}

function idURL($identity)
{
    return buildURL('idpage') . "?user=" . $identity;
}

function idFromURL($url)
{
    if (strpos($url, 'idpage') === false) {
        return null;
    }

    $parsed = parse_url($url);

    $q = $parsed['query'];

    $parts = array();
    parse_str($q, $parts);

    return @$parts['user'];
}















function authCancel($info)
{
    if ($info) {
        setRequestInfo();
        $url = $info->getCancelURL();
    } else {
        $url = getServerURL();
    }
    return $this->redirect($url);
}

function doAuth($info, $trusted=null, $fail_cancels=false,
                $idpSelect=null)
{
    if (!$info) {
        // There is no authentication information, so bail
        return authCancel(null);
    }

    if ($info->idSelect()) {
        if ($idpSelect) {
            $req_url = idURL($idpSelect);
        } else {
            $trusted = false;
        }
    } else {
        $req_url = $info->identity;
    }

    $user = getLoggedInUser();
    setRequestInfo($info);

    if ((!$info->idSelect()) && ($req_url != idURL($user))) {
        return login_render(array(), $req_url, $req_url);
    }

    $trust_root = $info->trust_root;

    if ($trusted) {
        setRequestInfo();
        $server =& getServer();
        $response =& $info->answer(true, null, $req_url);

        // Answer with some sample Simple Registration data.
        $sreg_data = array(
                           'fullname' => 'Example User',
                           'nickname' => 'example',
                           'dob' => '1970-01-01',
                           'email' => 'invalid@example.com',
                           'gender' => 'F',
                           'postcode' => '12345',
                           'country' => 'ES',
                           'language' => 'eu',
                           'timezone' => 'America/New_York');

        // Add the simple registration response values to the OpenID
        // response message.
        $sreg_request = Auth_OpenID_SRegRequest::fromOpenIDRequest(
                                              $info);

        $sreg_response = Auth_OpenID_SRegResponse::extractResponse(
                                              $sreg_request, $sreg_data);

        $sreg_response->toMessage($response->fields);

        // Generate a response to send to the user agent.
        $webresponse =& $server->encodeResponse($response);

        $new_headers = array();

        foreach ($webresponse->headers as $k => $v) {
            $new_headers[] = $k.": ".$v;
        }

        return array($new_headers, $webresponse->body);
    } elseif ($fail_cancels) {
        return authCancel($info);
    } else {
        return trust_render($info);
    }
}













function display_defines(){
define('page_template',
'<html>
  <head>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <title>%s</title>
%s
  </head>
  <body>
    %s
<div id="content">
    <h1>%s</h1>
    %s
</div>
  </body>
</html>');

define('logged_in_pat', 'You are logged in as %s (URL: %s)');

/**
 * HTTP response line contstants
 */
define('http_bad_request', 'HTTP/1.1 400 Bad Request');
define('http_found', 'HTTP/1.1 302 Found');
define('http_ok', 'HTTP/1.1 200 OK');
define('http_internal_error', 'HTTP/1.1 500 Internal Error');

/**
 * HTTP header constants
 */
define('header_connection_close', 'Connection: close');
define('header_content_text', 'Content-Type: text/plain; charset=us-ascii');

define('redirect_message',
       'Please wait; you are being redirected to <%s>');
}

/**
 * Return a string containing an anchor tag containing the given URL
 *
 * The URL does not need to be quoted, but if text is passed in, then
 * it does.
 */
function link_render($url, $text=null) {
    $esc_url = htmlspecialchars($url, ENT_QUOTES);
    $text = ($text === null) ? $esc_url : $text;
    return sprintf('<a href="%s">%s</a>', $esc_url, $text);
}


function navigation_render($msg, $items)
{
    $what = link_render(buildURL(), 'PHP OpenID Server');
    if ($msg) {
        $what .= ' &mdash; ' . $msg;
    }
    if ($items) {
        $s = '<p>' . $what . '</p><ul class="bottom">';
        foreach ($items as $action => $text) {
            $url = buildURL($action);
            $s .= sprintf('<li>%s</li>', link_render($url, $text));
        }
        $s .= '</ul>';
    } else {
        $s = '<p class="bottom">' . $what . '</p>';
    }
    return sprintf('<div class="navigation">%s</div>', $s);
}

/**
 * Render an HTML page
 */
function page_render($body, $user, $title, $h1=null, $login=false)
{
    $h1 = $h1 ? $h1 : $title;

    if ($user) {
        $msg = sprintf(logged_in_pat, link_render(idURL($user), $user),
                       link_render(idURL($user)));
        $nav = array('logout' => 'Log Out');

        $navigation = navigation_render($msg, $nav);
    } else {
        if (!$login) {
            $msg = link_render(buildURL('login'), 'Log In');
            $navigation = navigation_render($msg, array());
        } else {
            $navigation = '';
        }
    }

    $style = getStyle();
    $text = sprintf(page_template, $title, $style, $navigation, $h1, $body);
    // No special headers here
    $headers = array();
    return array($headers, $text);
}






















function idpXrds_render()
{

define('idp_xrds_pat', '<?xml version="1.0" encoding="UTF-8"?>
<xrds:XRDS
    xmlns:xrds="xri://$xrds"
    xmlns="xri://$xrd*($v*2.0)">
  <XRD>
    <Service priority="0">
      <Type>%s</Type>
      <URI>%s</URI>
    </Service>
  </XRD>
</xrds:XRDS>
');
    $headers = array('Content-type: application/xrds+xml');

    $body = sprintf(idp_xrds_pat,
                    Auth_OpenID_TYPE_2_0_IDP,
                    buildURL());

    return array($headers, $body);
}







function idpage_render($identity)
{
       '<link rel="openid2.provider openid.server" href="%s"/>
  <meta http-equiv="X-XRDS-Location" content="%s" />';

    $xrdsurl = buildURL('userXrds')."?user=".urlencode($identity);

    $headers = array(
                     'X-XRDS-Location: '.$xrdsurl);


    $body = sprintf(idpage_pat,
                    buildURL(),
                    $xrdsurl);
}










function userXrds_render($identity)
{include_once "Auth/OpenID/Discover.php";

define('user_xrds_pat', '<?xml version="1.0" encoding="UTF-8"?>
<xrds:XRDS
    xmlns:xrds="xri://$xrds"
    xmlns="xri://$xrd*($v*2.0)">
  <XRD>
    <Service priority="0">
      <Type>%s</Type>
      <Type>%s</Type>
      <URI>%s</URI>
    </Service>
  </XRD>
</xrds:XRDS>
');

    $headers = array('Content-type: application/xrds+xml');

    $body = sprintf(user_xrds_pat,
                    Auth_OpenID_TYPE_2_0,
                    Auth_OpenID_TYPE_1_1,
                    buildURL());

    return array($headers, $body);
}
					}