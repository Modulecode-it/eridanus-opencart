<?php
class ModelExtensionModuleShoputilsAntispam extends Model {
    private $log;
    private static $LOG_OFF = 0;
    private static $LOG_SPAM = 1;
    private static $LOG_FULL = 2;

    public function setCookie($cookie = 'contact') {
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $log = $cookie;
            $cookie = md5($this->request->server['HTTP_HOST'] . $cookie);
            $value = time();
            setcookie($cookie, $value, time() + 60 * 60 * 24 * 5, '/', $this->request->server['HTTP_HOST']);
            $this->logWrite('SET COOKIE: ' . $cookie . ' => ' . $value, self::$LOG_FULL, $log);
        }
    }

    public function deleteCookie($cookie = 'contact') {
        $cookie = md5($this->request->server['HTTP_HOST'] . $cookie);
        setcookie($cookie, '', time() - 3600, '/', $this->request->server['HTTP_HOST']);
    }

    public function validateContact() {
        if (!$this->config->get('shoputils_antispam_contact_status')) {
            return true;
        }

        if ($this->validateSubmit() && $this->validateCookie() && $this->validateIp() && $this->validateWord()) {
            $this->logWrite('  COOKIE:' . var_export($this->request->cookie, true), self::$LOG_FULL);
            $this->logWrite('  POST:' . var_export($this->request->post, true), self::$LOG_FULL);
            $this->logWrite('  GET:' . var_export($this->request->get, true), self::$LOG_FULL);

            //$this->deleteCookie();

            return true;
        } else {
            $this->logWrite('SPAM DETECTED! Mail not send!', self::$LOG_SPAM);
            $this->logWrite('  COOKIE:' . var_export($this->request->cookie, true), self::$LOG_SPAM);
            $this->logWrite('  POST:' . var_export($this->request->post, true), self::$LOG_SPAM);
            $this->logWrite('  GET:' . var_export($this->request->get, true), self::$LOG_SPAM);

            $this->deleteCookie();

            $this->response->redirect($this->url->link($this->getRoute(), '', 'SSL'));
        }
    }

    public function validateRegistr() {
        if (!$this->config->get('shoputils_antispam_contact_registr_status')) {
            return true;
        }

        if ($this->validateSubmit() && $this->validateCookie('registr') && $this->validateIp()) {
            $this->logWrite('  COOKIE:' . var_export($this->request->cookie, true), self::$LOG_FULL, 'registr');
            $this->logWrite('  POST:' . var_export($this->request->post, true), self::$LOG_FULL, 'registr');
            $this->logWrite('  GET:' . var_export($this->request->get, true), self::$LOG_FULL, 'registr');

            //$this->deleteCookie('registr');

            return true;
        } else {
            $this->logWrite('BOT DETECTED! Customer not Registered!', self::$LOG_SPAM, 'registr');
            $this->logWrite('  COOKIE:' . var_export($this->request->cookie, true), self::$LOG_SPAM, 'registr');
            $this->logWrite('  POST:' . var_export($this->request->post, true), self::$LOG_SPAM, 'registr');
            $this->logWrite('  GET:' . var_export($this->request->get, true), self::$LOG_SPAM, 'registr');

            $this->deleteCookie('registr');

            $this->response->redirect($this->url->link($this->getRoute('registr'), '', 'SSL'));
        }
    }

    protected function getRoute($action = 'contact') {
        if ($this->config->get('shoputils_antispam_contact_not_found')) {
            return 'error/not_found';
        } else {
            return $action == 'contact' ? 'information/contact/success' : 'account/success';
        }
    }

    protected function validateSubmit() {
        return !isset($this->request->post['submit']);
    }

    protected function validateCookie($cookie = 'contact') {
        return isset($this->request->cookie[md5($this->request->server['HTTP_HOST'] . $cookie)]) && ((((int)$this->request->cookie[md5($this->request->server['HTTP_HOST'] . $cookie)] + 20) <= time()) && (((int)$this->request->cookie[md5($this->request->server['HTTP_HOST'] . $cookie)] + 60 * 60 * 24 * 5) >= time()));
    }

    protected function validateIp() {
        $ips = trim(preg_replace('~\r?\n~', "\n", $this->config->get('shoputils_antispam_contact_ip')));
        $ips = explode("\n", trim($ips));

        foreach ($ips as $ip) {
            $ip = trim($ip);

            if (empty($ip)) continue;

            if (isset($this->request->server['REMOTE_ADDR']) && ($this->request->server['REMOTE_ADDR']) == $ip) {
                $this->logWrite('REMOTE_ADDR: ' . $ip, self::$LOG_SPAM);
                return false;
            }
        }

        return true;
    }

    protected function validateWord() {
        $words = trim(preg_replace('~\r?\n~', "\n", $this->config->get('shoputils_antispam_contact_word')));
        $words = explode("\n", trim($words));

        if (isset($this->request->post['enquiry'])) {
            foreach ($words as $word) {
                $word = trim($word);

                if (empty($word)) continue;

                if (strpos($this->request->post['enquiry'], $word) !== false) {
                    $this->logWrite('STOP-WORD: ' . $word, self::$LOG_SPAM);
                    return false;
                }
            }
        }

        return true;
    }

    protected function logWrite($message, $type, $log = 'contact') {
        $file_name = $log == 'contact' ? $this->config->get('shoputils_antispam_contact_log_filename') : $this->config->get('shoputils_antispam_contact_registr_log_filename');
        $log = $log == 'contact' ? $this->config->get('shoputils_antispam_contact_log') : $this->config->get('shoputils_antispam_contact_registr_log');

        switch ($log) {
            case self::$LOG_OFF:
                return;
            case self::$LOG_SPAM:
                if ($type == self::$LOG_FULL) {
                    return;
                }
        }

        if (!$this->log) {
            $this->log = new Log($file_name);
        }

        $this->log->Write($message);
    }
}
