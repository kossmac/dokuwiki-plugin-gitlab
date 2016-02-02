<?php
/**
 * Plugin GitLab: Gets commit message by project name and commit id.
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Karsten Kosmala <kosmalakarsten@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_gitlab extends DokuWiki_Syntax_Plugin {
    public function getType() { return 'substition'; }
    public function getSort() { return 32; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[\[gitlabapi>[a-zA-Z0-9.-]+>[a-z0-9]+\]\]', $mode, 'plugin_gitlab');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        list($name, $repository_name, $commit_id) = explode('>', $match);
        $commit_id_short = substr($commit_id, 0, -2);
        list($repository_id, $web_url)= $this->getInfoByName($repository_name);
        list($commit_msg, $commit_id_long) = $this->getInfoByHash($repository_id, $commit_id_short);

        return array($web_url, $commit_id_long, $commit_msg);
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
    // $data is what the function handle return'ed.
        if($mode == 'xhtml'){
            /** @var Doku_Renderer_xhtml $renderer */
            $renderer->doc .= '<a target="_blank" href="' . htmlspecialchars($data[0]) . '/commit/' . htmlspecialchars($data[1]) . '">' . htmlspecialchars($data[2]) . '</a>';
            return true;
        }
        return false;
    }

    public function getInfoByName($name) {
        $gitlabServer = $this->getConf('server');
        $apiToken = $this->getConf('api_token');
        $http = new DokuHTTPClient();
        $reqUrl = $gitlabServer . '/api/v3/projects/search/' . $name . '/?private_token=' . $apiToken;
        $repositories = json_decode($http->get($reqUrl), true);

        foreach ($repositories as &$repository) {
            if ($repository['name'] == $name) {
                $data = $repository;
            }
        }

        return array($data['id'], $data['web_url']);
    }

    public function getInfoByHash($repository_id, $commit_id) {
        $gitlabServer = $this->getConf('server');
        $apiToken = $this->getConf('api_token');
        $http = new DokuHTTPClient();
        $reqUrl = $gitlabServer . '/api/v3/projects/' . $repository_id . '/repository/commits/' . $commit_id . '/?private_token=' .$apiToken;
        $data = json_decode($http->get($reqUrl), true);

        return array($data['message'], $data['id']);
    }
}
