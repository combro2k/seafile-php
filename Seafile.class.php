<?php

class Seafile
{
    /**
     * @var
     */
    private $_token;

    /**
     * @var
     */
    private $username;
    /**
     * @var
     */
    private $password;

    /**
     * @var
     */
    private $hostname;

    /**
     * @param $hostname
     * @param $username
     * @param $password
     */
    public function __construct($hostname, $username, $password)
    {
        $this->setHostname($hostname);
        $this->setUsername($username);
        $this->setPassword($password);

        $this->Login();
    }

    private function Login()
    {
        $fields = array(
            'username' => urlencode($this->getUsername()),
            'password' => urlencode($this->getPassword()),
        );

        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getHostname() . '/api2/auth-token/');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = json_decode(curl_exec($ch), true);

        curl_close($ch);

        $this->setToken($result['token']);

        return true;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param mixed $hostname
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->_token = $token;
    }

    public function getUploadLink($repo)
    {
        $uploadLink = $this->api('GET', '/api2/repos/' . $repo . '/upload-link/');
        $uploadLink = '/seafhttp/upload-api/' . strrev(array_shift(preg_split('/\//', strrev($uploadLink), 2)));

        return $uploadLink;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $data
     *
     * @return mixed
     */
    public function api($method = 'GET', $path = '', $data = array())
    {
        $ch = curl_init();

        if (!preg_match('/^http(s|):/i', $path)) {
            $url = $this->getHostname() . $path;
        } else {
            $url = $path;
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        switch ($method) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, count($data));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token ' . $this->_token,
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);

        if (curl_error($ch) || !isset($result)) {
            curl_error($ch);
        }

        curl_close($ch);

        return json_decode($result, true);
    }

    /**
     * @param        $filename
     * @param        $uploadLink
     * @param string $path
     *
     * @return string
     */
    public function upload($filename, $uploadLink, $path = '/Uploaded')
    {
        return $this->api('POST', $uploadLink, array(
            'file'       => "@$filename",
            'filename'   => basename($filename),
            'parent_dir' => $path,
        ));
    }
}