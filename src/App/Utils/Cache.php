<?php
namespace App\Utils;

use SimpleXMLElement;
use DOMDocument;

class Cache
{

    /**
     * 
     * @var CacheField $cache
     */
    private $root;
    private $filename;
    
    public function __construct($filename = '')
    {
        $xmlstr = '';
        
        if (file_exists($filename)) {
            $xmlstr = file_get_contents($filename);
        }
        
        $this->filename = $filename;
        $this->root = new CacheField(new SimpleXMLElement($xmlstr));
    }
    
    /**
     * 
     * @param string $key
     * @return CacheFieldInterface
     */
    public function get($key){
        return $this->root->get($key);
    }
    
    public function getXml(){
        return $this->root->getXml();
    }
    
    /**
     * 
     * @param string $key
     * @param string $value
     * @return CacheFieldInterface
     */
    public function set($key, $value){
        return $this->root->set($key, $value);
    }
    
    public function persist()
    {
        $doc = new DOMDocument('1.0');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($this->root->getXml()->asXML());
        
        return $doc->save($this->filename);
    }
    
}

