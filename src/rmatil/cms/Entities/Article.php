<?php

namespace rmatil\cms\Entities;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type;

/**
 * @ORM\Entity
 * @ORM\Table(name="articles")
 **/
class Article {

    /**
     * Id of the article
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @Type("integer")
     *
     * @var integer
     */
    protected $id;

    /**
     * Url name for the article
     *
     * @ORM\Column(type="string")
     *
     * @Type("string")
     *
     * @var string
     */
    protected $urlName;

    /**
     * The category to which the article belongs
     *
     * @ORM\ManyToOne(targetEntity="ArticleCategory", cascade="persist")
     *
     * @Type("rmatil\cms\Entities\ArticleCategory")
     *
     * @var \rmatil\cms\Entities\ArticleCategory
     */
    protected $category;

    /**
     * The author of this article
     *
     * @ORM\ManyToOne(targetEntity="User", cascade="persist")
     *
     * @Type("rmatil\cms\Entities\User")
     *
     * @var \rmatil\cms\Entities\User
     */
    protected $author;

    /**
     * The language of this article
     *
     * @ORM\ManyToOne(targetEntity="Language", cascade="persist")
     *
     * @Type("rmatil\cms\Entities\Language")
     *
     * @var \rmatil\cms\Entities\Language
     */
    protected $language;

    /**
     * Title of the article
     *
     * @ORM\Column(type="string")
     *
     * @Type("string")
     *
     * @var string
     */
    protected $title;

    /**
     * Body of the article
     *
     * @ORM\Column(type="text")
     *
     * @Type("string")
     *
     * @var string
     */
    protected $content;

    /**
     * DateTime object of the last edit date
     * May be null
     *
     * @ORM\Column(type="datetime")
     *
     * @Type("DateTime<'Y-m-d\TH:i:sP', 'UTC'>")
     *
     * @var \DateTime
     */
    protected $lastEditDate;

    /**
     * DateTime object of the creation date
     * May be null
     *
     * @ORM\Column(type="datetime")
     *
     * @Type("DateTime<'Y-m-d\TH:i:sP', 'UTC'>")
     *
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * Indicates whether this article is locked
     * for editing or not
     *
     * @ORM\ManyToOne(targetEntity="User", cascade="persist")
     *
     * @Type("rmatil\cms\Entities\User")
     *
     * @var \rmatil\cms\Entities\User
     */
    protected $isLockedBy;

    /**
     * Indicates whether the article should be published or not
     *
     * @ORM\Column(type="boolean")
     *
     * @Type("boolean")
     *
     * @var boolean
     */
    protected $isPublished = false;

    /**
     * Page to which this article belongs
     *
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="articles")
     *
     * @Type("rmatil\cms\Entities\Page")
     *
     * @var \rmatil\cms\Entities\Page
     */
    protected $page;

    /**
     * All user groups which are allowed to access this article
     *
     * THIS IS THE INVERSE SIDE. CORRESPONDING RELATION IN USERGROUP MUST BE UPDATED MANUALLY
     * @see \rmatil\cms\Entities\UserGroup::$accessibleArticles
     * @link http://docs.doctrine-project.org/en/latest/reference/working-with-associations.html#working-with-associations
     *
     * @ORM\ManyToMany(targetEntity="UserGroup", mappedBy="accessibleArticles")
     * @ORM\JoinTable(name="usergroup_articles")
     *
     * @Type("ArrayCollection<rmatil\cms\Entities\UserGroup>")
     *
     * @var ArrayCollection[rmatil\cms\Entities\UserGroup]
     */
    protected $allowedUserGroups;


    public function __construct() {
        $this->content = '';
        $this->creationDate = new DateTime();
        $this->lastEditDate = new DateTime();
        $this->urlName = '';
        $this->title = '';
        $this->isPublished = true;
        $this->allowedUserGroups = new ArrayCollection();
    }


    /**
     * Gets the Id of the article.
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the Id of the article.
     *
     * @param integer $id the id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Gets the Url name for the article.
     *
     * @return string
     */
    public function getUrlName() {
        return $this->urlName;
    }

    /**
     * Sets the Url name for the article.
     *
     * @param string $urlName the article url name
     */
    public function setUrlName($urlName) {
        $this->urlName = $urlName;
    }

    /**
     * Gets the The category to which the article belongs.
     *
     * @return \rmatil\cms\Entities\ArticleCategory
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * Sets the The category to which the article belongs.
     *
     * @param \rmatil\cms\Entities\ArticleCategory $category the article category
     */
    public function setCategory(ArticleCategory $category = null) {
        $this->category = $category;
    }

    /**
     * Gets the The author of this article.
     *
     * @return \rmatil\cms\Entities\User
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Sets the The author of this article.
     *
     * @param \rmatil\cms\Entities\User $author the author
     */
    public function setAuthor(User $author = null) {
        $this->author = $author;
    }

    /**
     * Gets the The languate of this article.
     *
     * @return \rmatil\cms\Entities\Language
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * Sets the The languate of this article.
     *
     * @param \rmatil\cms\Entities\Language $language the language
     */
    public function setLanguage(Language $language = null) {
        $this->language = $language;
    }

    /**
     * Gets the Title of the article.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the Title of the article.
     *
     * @param string $title the title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Gets the Body of the article.
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Sets the Body of the article.
     *
     * @param string $content the content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * Gets the DateTime object of the last edit date
     * May be null.
     *
     * @return \DateTime
     */
    public function getLastEditDate() {
        return $this->lastEditDate;
    }

    /**
     * Sets the DateTime object of the last edit date
     * May be null.
     *
     * @param \DateTime $lastEditDate the last edit date
     */
    public function setLastEditDate(\DateTime $lastEditDate = null) {
        $this->lastEditDate = $lastEditDate;
    }

    /**
     * Gets the DateTime object of the creation date
     * May be null.
     *
     * @return \DateTime
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * Sets the DateTime object of the creation date
     * May be null.
     *
     * @param \DateTime $creationDate the creation date
     */
    public function setCreationDate(\DateTime $creationDate = null) {
        $this->creationDate = $creationDate;
    }

    /**
     * Gets the user which locks this user
     *
     * @return \rmatil\cms\Entities\User
     */
    public function getIsLockedBy() {
        return $this->isLockedBy;
    }

    /**
     * Sets the user which locks this article
     *
     * @param \rmatil\cms\Entities\User $isLocked the user which locks the article
     */
    public function setIsLockedBy($isLockedBy) {
        $this->isLockedBy = $isLockedBy;
    }

    /**
     * Gets the Indicates whether the article should be published or not.
     *
     * @return boolean
     */
    public function getIsPublished() {
        return $this->isPublished;
    }

    /**
     * Sets the Indicates whether the article should be published or not.
     *
     * @param boolean $isPublished the is public
     */
    public function setIsPublished($isPublished) {
        $this->isPublished = $isPublished;
    }

    /**
     * Gets the Page to which this article belongs.
     *
     * @return \rmatil\cms\Entities\Page
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * Sets the Page to which this article belongs.
     *
     * @param \rmatil\cms\Entities\Page $page the page
     */
    public function setPage(Page $page = null) {
        $this->page = $page;
    }

    /**
     * Gets all user groups which are allowed to access this article
     *
     * @return ArrayCollection
     */
    public function getAllowedUserGroups() {
        return $this->allowedUserGroups;
    }

    /**
     * Sets all user groups which are allowed to access this article.
     *
     * THIS IS THE INVERSE SIDE. CORRESPONDING RELATION IN USERGROUP MUST BE UPDATED MANUALLY
     * @see \rmatil\cms\Entities\UserGroup::$accessibleArticles
     *
     * @param ArrayCollection $allowedUserGroups The user groups which may access this article
     */
    public function setAllowedUserGroups($allowedUserGroups) {
        $this->allowedUserGroups = $allowedUserGroups;
    }

    /**
     * Adds an user group which may access this article.
     *
     * THIS IS THE INVERSE SIDE. CORRESPONDING RELATION IN USERGROUP MUST BE UPDATED MANUALLY
     * @see \rmatil\cms\Entities\UserGroup::$accessibleArticles
     *
     * @param UserGroup $userGroup The user group to allow access to
     */
    public function addAllowedUserGroup(UserGroup $userGroup) {
        $this->allowedUserGroups[] = $userGroup;
    }

    /**
     * Removes the access to this article from the given user group.
     *
     * THIS IS THE INVERSE SIDE. CORRESPONDING RELATION IN USERGROUP MUST BE UPDATED MANUALLY
     * @see \rmatil\cms\Entities\UserGroup::$accessibleArticles
     *
     * @param UserGroup $userGroup The user group from which to revoke access from
     */
    public function removeAllowedUserGroup(UserGroup $userGroup) {
        $this->allowedUserGroups->removeElement($userGroup);
    }

}