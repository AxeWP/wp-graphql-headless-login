<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */ namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * @property array $response
 * @property string $uid
 */
class LinkedInResourceOwner extends GenericResourceOwner
{

    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response = [];

    /**
     * Sorted profile pictures
     *
     * @var array
     */
    protected $sortedProfilePictures = [];

    /**
     * @var string|null
     */
    private $email;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
        $this->setSortedProfilePictures();
    }

    /**
     * Gets resource owner attribute by key. The key supports dot notation.
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        return $this->getValueByKey($this->response, (string) $key);
    }

    /**
     * Get user first name
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->getAttribute('localizedFirstName');
    }

    /**
     * Get user user id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * Get specific image by size
     *
     * @param integer $size
     * @return array|null
     */
    public function getImageBySize($size)
    {
        $pictures = array_filter($this->sortedProfilePictures, function ($picture) use ($size) {
            return isset($picture['width']) && $picture['width'] == $size;
        });

        return count($pictures) ? $pictures[0] : null;
    }

    /**
     * Get available user image sizes
     *
     * @return array
     */
    public function getImageSizes()
    {
        return array_map(function ($picture) {
            return $this->getValueByKey($picture, 'width');
        }, $this->sortedProfilePictures);
    }

    /**
     * Get user image url
     *
     * @return string|null
     */
    public function getImageUrl()
    {
        $pictures = $this->getSortedProfilePictures();
        $picture = array_pop($pictures);

        return $picture ? $this->getValueByKey($picture, 'url') : null;
    }

    /**
     * Get user last name
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->getAttribute('localizedLastName');
    }

    /**
     * Returns the sorted collection of profile pictures.
     *
     * @return array
     */
    public function getSortedProfilePictures()
    {
        return $this->sortedProfilePictures;
    }

    /**
     * Get user url
     *
     * @return string|null
     */
    public function getUrl()
    {
        $vanityName = $this->getAttribute('vanityName');

        return $vanityName ? sprintf('https://www.linkedin.com/in/%s', $vanityName) : null;
    }

    /**
     * Get user email, if available
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getAttribute('email');
    }

    /**
     * Attempts to sort the collection of profile pictures included in the profile
     * before caching them in the resource owner instance.
     *
     * @return void
     */
    private function setSortedProfilePictures()
    {
        $pictures = $this->getAttribute('profilePicture.displayImage~.elements');
        if (is_array($pictures)) {
            $pictures = array_filter($pictures, function ($element) {
                // filter to public images only
                return
                    isset($element['data']['com.linkedin.digitalmedia.mediaartifact.StillImage'])
                    && strtoupper($element['authorizationMethod']) === 'PUBLIC'
                    && isset($element['identifiers'][0]['identifier'])
                ;
            });
            // order images by width, LinkedIn profile pictures are always squares, so that should be good enough
            usort($pictures, function ($elementA, $elementB) {
                $wA = $elementA['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['storageSize']['width'];
                $wB = $elementB['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['storageSize']['width'];
                return $wA - $wB;
            });
            $pictures = array_map(function ($element) {
                // this is an URL, no idea how many of identifiers there can be, so take the first one.
                $url = $element['identifiers'][0]['identifier'];
                $type = $element['identifiers'][0]['mediaType'];
                $width = $element['data']['com.linkedin.digitalmedia.mediaartifact.StillImage']['storageSize']['width'];
                return [
                    'width' => $width,
                    'url' => $url,
                    'contentType' => $type,
                ];
            }, $pictures);
        } else {
            $pictures = [];
        }

        $this->sortedProfilePictures = $pictures;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
