<?php

namespace Psr7Session;

use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

class SessionStorage implements SessionStorageInterface
{
    protected $id;
    protected $name;
    protected $bags;
    protected $started = false;
    protected $closed = false;
    protected $regenerated = false;
    protected $saveHandler;
    protected $metadataBag; 

    protected function generateSessionId() {
        return hash('sha256', microtime());
    }   

    public function __construct($handler, $id = null, $name = null, MetadataBag $metaBag = null) {
        $this->id = $id ?: $this->generateSessionId();
        $this->name = $name ?: 'Psr7Session';
        $this->setMetadataBag($metaBag);
        $this->setSaveHandler($handler);
    }

    protected function loadSession(array &$session = null)
    {
        if (null === $session) {
            $session = unserialize($this->saveHandler->read($this->id));
        }
        $bags = array_merge($this->bags, array($this->metadataBag));
        foreach ($bags as $bag) {
            $key = $bag->getStorageKey();
            $session[$key] = isset($session[$key]) ? $session[$key] : [];
            $bag->initialize($session[$key]);
        }
        $this->started = true;
        $this->closed = false;
    }

    public function start() {
        if ($this->started) {
            return true;
        }

        $this->loadSession();
        return true;
    }

    public function isStarted() {
        return $this->started;
    }

    public function isRegenerated() {
        return $this->regenerated;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function regenerate($destroy = false, $lifetime = null) {
        $this->id = $this->generateSessionId();

        if ($destroy) {
            $this->metadataBag->stampNew();
        }

        $this->regenerated = true;
        return true;
    }

    public function save() {
        $session = [];

        foreach ($this->bags as $bag) {
            $key = $bag->getStorageKey();
            $session[$key] = $bag->all();
        }

        $this->saveHandler->write($this->id, serialize($session));
        $this->closed = true;
        $this->started = false;
    }

    public function clear() {
        foreach ($this->bags as $bag) {
            $bag->clear();
        }
        $this->saveHandler->write($this->id, serialize([]));
        $this->loadSession();
    }

    public function getBag($name) {
        if (!isset($this->bags[$name])) {
            throw new \InvalidArgumentException(sprintf('The SessionBagInterface %s is not registered.', $name));
        }

        if (!$this->started) {
            $this->start();
        }

        return $this->bags[$name];
    }

    public function registerBag(SessionBagInterface $bag)
    {
        if ($this->started) {
            throw new \LogicException('Cannot register a bag when the session is already started.');
        }

        $this->bags[$bag->getName()] = $bag;
    }

    public function getMetadataBag() {
        return $this->metadataBag;
    }

    public function setMetadataBag(MetadataBag $metaBag = null)
    {
        if (null === $metaBag) {
            $metaBag = new MetadataBag();
        }

        $this->metadataBag = $metaBag;
    }

    public function setSaveHandler($saveHandler = null)
    {
        $this->saveHandler = $saveHandler;
    }
}
