<?php
namespace CustomerParadigm\AmazonPersonalize\Override\View\Design\Theme;

/**
 * Theme factory
 */
class FlyweightFactory extends \Magento\Framework\View\Design\Theme\FlyweightFactory
{
    protected $themeProvider;

    /**
     * Constructor
     *
     * @param ThemeProviderInterface $themeProvider
     */
    public function __construct(
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $themeProvider
    ) {
        parent::__construct($themeProvider);
        $this->themeProvider = $themeProvider;
    }

    /**
     * Creates or returns a shared model of theme
     *
     * Search for theme in File System by specific path or load theme from DB
     * by specific path (e.g. adminhtml/Magento/backend) or by identifier (theme primary key) and return it
     * Can be used to deploy static or in other setup commands, even if Magento is not installed yet.
     *
     * @param string $themeKey Should looks like Magento/backend or should be theme primary key
     * @param string $area Can be adminhtml, frontend, etc...
     * @return \Magento\Framework\View\Design\ThemeInterface
     * @throws \InvalidArgumentException when incorrect $themeKey was specified
     * @throws \LogicException when theme with appropriate $themeKey was not found
     */
    public function create($themeKey, $area = \Magento\Framework\View\DesignInterface::DEFAULT_AREA)
    {
    // Bug fix: This module is looking for themeKey when Personalize functions are called via cron.
        // There isn't one in that case, so this sets it to default '3' instead of throwing the error.
        if (!is_numeric($themeKey) && !is_string($themeKey)) {
            if ($area == 'crontab') {
                $themeKey = 3;
            } else {
                throw new \InvalidArgumentException('Incorrect theme identification key');
            }
        }
        $themeKey = $this->extractThemeId($themeKey);
        if (is_numeric($themeKey)) {
            $themeModel = $this->_loadById($themeKey);
        } else {
            $themeModel = $this->_loadByPath($themeKey, $area);
        }
        if (!$themeModel->getCode()) {
            throw new \LogicException("Unable to load theme by specified key: '{$themeKey}'");
        }
        $this->_addTheme($themeModel);
        return $themeModel;
    }

    /**
     * Attempt to determine a numeric theme ID from the specified path
     *
     * @param string $path
     * @return string
     */
    private function extractThemeId($path)
    {
        $dir = \Magento\Framework\View\DesignInterface::PUBLIC_THEME_DIR;
        if (preg_match('/^' . preg_quote($dir, '/') . '(\d+)$/', $path, $matches)) {
            return $matches[1];
        }
        return $path;
    }
}
