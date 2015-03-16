<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Navigation\Page;

use Pimcore\Model\Document;

class Uri extends \Zend_Navigation_Page_Uri
{

    /**
     * @var string
     */
    protected $_accesskey;

    /**
     * @var string
     */
    protected $_tabindex;

    /**
     * @var string
     */
    protected $_relation;

    /**
     * @var int
     */
    protected $_documentId;

    /**
     * @var string
     */
    protected $documentType;

    /**
     * @var string
     */
    protected $realFullPath;


    /**
     * @param  $tabindex
     * @return void
     */
    public function setTabindex($tabindex)
    {
        $this->_tabindex = $tabindex;
        return $this;
    }

    /**
     * @return string
     */
    public function getTabindex()
    {
        return $this->_tabindex;
    }

    /**
     * @param null $character
     * @return $this|\Zend_Navigation_Page
     */
    public function setAccesskey($character = null)
    {
        $this->_accesskey = $character;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccesskey()
    {
        return $this->_accesskey;
    }

    /**
     * @param  $relation
     * @return void
     */
    public function setRelation($relation)
    {
        $this->_relation = $relation;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelation()
    {
        return $this->_relation;
    }

    /**
     * @param $document
     * @return $this
     */
    public function setDocument($document)
    {
        if($document instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            $this->setDocumentId($document->getHardlinkSource()->getId());
            $this->setDocumentType($document->getHardlinkSource()->getType());
            $this->setRealFullPath($document->getHardlinkSource()->getRealFullPath());
        } else if($document instanceof Document) {
            $this->setDocumentId($document->getId());
            $this->setDocumentType($document->getType());
            $this->setRealFullPath($document->getRealFullPath());
        }
        return $this;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        $docId = $this->getDocumentId();
        if($docId) {
            $doc = Document::getById($docId);
            if($doc instanceof Document\Hardlink) {
                $doc = Document\Hardlink\Service::wrap($doc);
            }
            return $doc;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->_documentId;
    }

    /**
     * @param int $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->_documentId = $documentId;
    }

    /**
     * @return mixed
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param mixed $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return string
     */
    public function getRealFullPath()
    {
        return $this->realFullPath;
    }

    /**
     * @param string $realFullPath
     */
    public function setRealFullPath($realFullPath)
    {
        $this->realFullPath = $realFullPath;
    }
}