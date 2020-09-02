<?php
namespace App\Utils;

use SimpleXMLElement;

class CacheField implements CacheFieldInterface
{
    /**
     * 
     * @var SimpleXMLElement
     */
    private $xml;
    
    public function __construct($xml)
    {
        $this->xml = $xml;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see CacheFieldInterface::set()
     */
    public function set($key, $value)
    {    
        if($res = $this->search($key)){
            $res['value'] = $value;
        } else {
            $field = $this->xml->addChild('field');
            $field->addAttribute('key', $key);
            $field->addAttribute('value', $value);
        }
        
        return $this;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see CacheFieldInterface::get()
     */
    public function get($key)
    {
        if($res = $this->search($key)){
            return new CacheField($res);
        }
        return null;
    }
    
    /**
     * 
     * @return string;
     */
    public function __toString(){
        return (string) $this->xml['value'];
    }
    
    /**
     * 
     * @return SimpleXMLElement
     */
    public function getXml() {
        return $this->xml;
    }
    
    /**
     * 
     * @param string $key
     * @return SimpleXMLElement|NULL
     */
    private function search($key)
    {
        $res = $this->xml->xpath("./field[@key='$key']");
        
        if(count($res))
            return $res[0];
        
        return null;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see CacheFieldInterface::remove()
     */
    public function remove($key)
    {
        if($res = $this->search($key)){
            $res->parentNode->removeChild($res);
        }
        
        return $this;
    }
    
}

