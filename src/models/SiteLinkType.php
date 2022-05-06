<?php

namespace vaersaagod\linkmate\models;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\models\Site;
use Throwable;
use vaersaagod\linkmate\fields\LinkField;
use yii\base\Model;

/**
 * Class SiteLinkType
 *
 * @package vaersaagod\linkmate\models
 *
 * @property-read string[] $defaultSettings
 */
class SiteLinkType extends Model implements LinkTypeInterface
{
    /**
     * @var string
     */
    public string $displayGroup = 'Common';

    /**
     * @var string
     */
    public string $displayName;


    /**
     * SiteLinkType constructor.
     *
     * @param string|array $displayName
     * @param array        $options
     */
    public function __construct($displayName, array $options = [])
    {
        if (is_array($displayName)) {
            $options = $displayName;
        } else {
            $options['displayName'] = $displayName;
        }

        parent::__construct($options);
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'sites' => '*',
        ];
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return Craft::t('linkmate', $this->displayName);
    }

    /**
     * @return string
     */
    public function getDisplayGroup(): string
    {
        return Craft::t('linkmate', $this->displayGroup);
    }

    /**
     * @param Link $link
     *
     * @return null|Site
     */
    public function getSite(Link $link): ?Site
    {
        if ($this->isEmpty($link)) {
            return null;
        }

        return Craft::$app->getSites()->getSiteById($link->value);
    }

    /**
     * @param string                $linkTypeName
     * @param LinkField             $field
     * @param Link                  $value
     * @param ElementInterface|null $element
     *
     * @return string
     */
    public function getInputHtml(string $linkTypeName, LinkField $field, Link $value, ElementInterface $element = null): string
    {
        $settings = $field->getLinkTypeSettings($linkTypeName, $this);
        $siteIds = $settings['sites'];
        $isSelected = $value->type === $linkTypeName;
        $selectedSite = $isSelected ? $this->getSite($value) : null;

        $selectFieldOptions = [
            'disabled' => $field->isStatic(),
            'id' => $field->handle.'-'.$linkTypeName,
            'name' => $field->handle.'['.$linkTypeName.']',
            'options' => $this->getSiteOptions($siteIds),
            'value' => $selectedSite->id ?? null,
        ];

        try {
            return Craft::$app->view->renderTemplate('linkmate/_input-select', [
                'isSelected' => $isSelected,
                'linkTypeName' => $linkTypeName,
                'selectFieldOptions' => $selectFieldOptions,
            ]);
        } catch (Throwable $throwable) {
            $message = Craft::t(
                'linkmate',
                'Error: Could not render the template for the field `{name}`.',
                ['name' => $this->getDisplayName()]
            );
            Craft::error($message . ' ' . $throwable->getMessage());

            return Html::tag('p', $message);
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function getLinkValue(mixed $value): mixed
    {
        return $value ?? null;
    }

    /**
     * @param string    $linkTypeName
     * @param LinkField $field
     *
     * @return string
     */
    public function getSettingsHtml(string $linkTypeName, LinkField $field): string
    {
        try {
            return Craft::$app->view->renderTemplate('linkmate/_settings-site', [
                'settings' => $field->getLinkTypeSettings($linkTypeName, $this),
                'elementName' => $this->getDisplayName(),
                'linkTypeName' => $linkTypeName,
                'siteOptions' => $this->getSiteOptions(),
            ]);
        } catch (Throwable $throwable) {
            $message = Craft::t(
                'linkmate',
                'Error: Could not render the template for the field `{name}`.',
                ['name' => $this->getDisplayName()]
            );
            Craft::error($message . ' ' . $throwable->getMessage());

            return Html::tag('p', $message);
        }
    }

    /**
     * @param array|string|null $siteIds
     *
     * @return array
     */
    protected function getSiteOptions(array|string $siteIds = null): array
    {
        if ($siteIds === '*') {
            $siteIds = null;
        } elseif ($siteIds === '') {
            $siteIds = [];
        }

        $options = array_map(static function($site) use ($siteIds) {
            if (!$site->hasUrls || (is_array($siteIds) && !in_array($site->id, $siteIds))) {
                return null;
            }

            return [
                'value' => $site->id,
                'label' => $site->name
            ];
        }, Craft::$app->getSites()->getAllSites());

        return array_filter($options);
    }

    /**
     * @param Link $link
     *
     * @return null|string
     */
    public function getText(Link $link): ?string
    {
        $site = $this->getSite($link);
        if (is_null($site)) {
            return null;
        }

        return (string)$site;
    }

    /**
     * @param Link $link
     *
     * @return null|string
     */
    public function getUrl(Link $link): ?string
    {
        $site = $this->getSite($link);
        if (is_null($site)) {
            return null;
        }

        return Craft::getAlias($site->baseUrl);
    }

    /**
     * @param Link $link
     *
     * @return bool
     */
    public function isEmpty(Link $link): bool
    {
        if (is_string($link->value)) {
            return trim($link->value) === '';
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateSettings(array $settings): array
    {
        return $settings;
    }

    /**
     * @param LinkField $field
     * @param Link      $link
     *
     * @return array|null
     */
    public function validateValue(LinkField $field, Link $link): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getElement(Link $link, bool $ignoreStatus = false): ?ElementInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function hasElement(Link $link, bool $ignoreStatus = false): bool
    {
        return false;
    }
}
