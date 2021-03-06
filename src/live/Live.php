<?php
namespace Goodspb\LiveSdk;

use Exception;

class Live
{
    protected $config = [];
    protected $upstream;

    public function __construct()
    {

    }

    public function setConfig(array $config)
    {
        return $this->config = array_replace_recursive($this->config, $config);
    }

    protected function config($key = null, $default = null)
    {
        return is_null($key) ? $this->config : fnGet($this->config, $key, $default);
    }

    public function setUpstream($upstream)
    {
        $this->config['upstream'] = $upstream;
        $this->upstream = $this->config['upstream'];
        $this->checkUpstream();
    }

    protected function checkUpstream()
    {
        $upstream = $this->upstream;
        if (empty($upstream)) {
            throw new Exception("Can't get live upstream settings.");
        }
        $totalRank = 0;
        foreach ($upstream as $agentName => $rank) {
            if (is_null($this->config('agents.' . $agentName))) {
                throw new Exception("Invalid upstream settings.");
            }
            $totalRank += $rank;
        }
        if (100 != $totalRank) {
            throw new Exception("Invalid upstream settings.");
        }
    }

    /**
     * @return AgentAbstract
     * @throws Exception
     */
    protected function getAgentFromUpstreamSetting()
    {
        $upstream = $this->upstream;
        $random = mt_rand(1, 100);
        $range = 0;
        $config = [];
        $agentName = '';
        foreach ($upstream as $agent => $rank) {
            $range += $rank;
            if ($random <= $range && ($config = $this->config("agents.{$agent}", []))
                && isset($config['enable']) && $config['enable']) {
                $agentName = $agent;
                break;
            }
        }
        if (empty($config)) {
            throw new Exception("No useful agent.");
        }
        $class = $config['class'];
        $instance = new $class($config, $agentName);
        if ($instance instanceof AgentAbstract) {
            return $instance;
        }
        throw new Exception("Agent adapter is not extends of AgentAbstract.");
    }

    /**
     * @param null $agent
     * @return AgentAbstract
     */
    public function getAgent($agent = null)
    {
        is_null($agent) or $this->setUpstream([$agent => 100]);
        return $this->getAgentFromUpstreamSetting();
    }

}
