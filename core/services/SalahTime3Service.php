<?php
namespace farazilu\salahtime3\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\Category;

class SalahTime3Service extends Component
{

    // Public Methods
    // =========================================================================
    public function getAllMenus()
    {
        return OlivemenusRecord::find()->all();
    }

    public function getMenuById($id)
    {
        $record = OlivemenusRecord::findOne([
            'id' => $id
        ]);
        return new OlivemenusModel($record->getAttributes());
    }

    public function getMenuByHandle($handle)
    {
        return OlivemenusRecord::findOne([
            'handle' => $handle
        ]);
    }

    public function getMenuByName($name)
    {
        return OlivemenusRecord::findOne([
            'name' => $name
        ]);
    }

    public function deleteMenuById($id)
    {
        $record = OlivemenusRecord::findOne([
            'id' => $id
        ]);

        if ($record) {
            Olivemenus::$plugin->olivemenuItems->deleteItemsByMenuId($record);
            if ($record->delete()) {
                return 1;
            }
            ;
        }
    }

    public function saveMenu(OlivemenusModel $model)
    {
        $record = false;
        if (isset($model->id)) {
            $record = OlivemenusRecord::findOne([
                'id' => $model->id
            ]);
        }

        if (! $record) {
            $record = new OlivemenusRecord();
        }

        $record->name = $model->name;
        $record->handle = $model->handle;

        $save = $record->save();
        if (! $save) {
            Craft::getLogger()->log($record->getErrors(), LOG_ERR, 'olivemenus');
        }
        return $save;
    }

    // Frotend Methods
    // =========================================================================
    public function getSahahTime3HTML($handle = false, $config)
    {
        if ($handle) {
            $localHTML = '';

            $menu = $this->getMenuByHandle($handle);

            if ($menu !== NULL) {
                $menu_id = '';
                $menu_class = '';
                $ul_class = '';
                $menu_items = Olivemenus::$plugin->olivemenuItems->getMenuItems($menu->id);

                if (! empty($config)) {
                    if (isset($config['menu-id'])) {
                        $menu_id = ' id="' . $config['menu-id'] . '"';
                    }
                    if (isset($config['menu-class'])) {
                        $menu_class .= ' ' . $config['menu-class'];
                    }
                    if (isset($config['ul-class'])) {
                        $ul_class = $config['ul-class'];
                    }
                }

                $localHTML .= '<div' . $menu_id . ' class="menu' . $menu_class . '">';
                $localHTML .= '<ul class="' . $ul_class . '">';
                foreach ($menu_items as $menu_item) {
                    $localHTML .= $this->getMenuItemHTML($menu_item, $config);
                }
                $localHTML .= '</ul>';
                $localHTML .= '</div>';
            } else {
                $localHTML .= '<p>' . Craft::t('olivemenus', 'A menu with this handle does not exit!') . '</p>';
            }
            echo $localHTML;
        }
    }

    private function getMenuItemHTML($menu_item, $config)
    {
        $menu_item_url = '';
        $menu_class = '';
        $ul_class = '';
        $menu_item_class = 'menu-item';
        $entry_id = $menu_item['entry_id'];
        $custom_url = $menu_item['custom_url'];
        $class = $menu_item['class'];
        $class_parent = $menu_item['class_parent'];

        $data_attributes = '';
        $data_json = $menu_item['data_json'];

        $menu_class = $class;
        $menu_item_class = $menu_item_class . ' ' . $class_parent;

        if (! empty($config)) {
            if (isset($config['li-class'])) {
                $menu_item_class .= ' ' . $config['li-class'];
            }

            if (isset($config['link-class'])) {
                $menu_class .= ' ' . $config['link-class'];
            }
        }

        if ($custom_url != '') {
            $menu_item_url = $this->replaceEnvironmentVariables($custom_url);
        } else {
            $entry = Entry::find()->id($menu_item['entry_id'])->one();

            if (! empty($entry))
                $menu_item_url = $entry->url;
            else {
                $entry = Category::find()->id($menu_item['entry_id'])->one();

                if (! empty($entry))
                    $menu_item_url = $entry->url;
            }
        }

        if ($data_json) {
            $data_attributes = ' ';
            $data_json = explode(PHP_EOL, $data_json);
            foreach ($data_json as $data_item) {
                $data_item = explode(':', $data_item);
                $data_attributes .= trim($data_item[0]) . '="' . trim($data_item[1]) . '"';
            }
        }

        // extract target option
        $target = $menu_item['target'];

        $current_active_url = Craft::$app->request->getServerName() . Craft::$app->request->getUrl();
        if ($current_active_url != '' && $menu_item_url != '') {
            $menu_item_url_filtered = preg_replace('#^https?://#', '', $menu_item_url);
            $current_active_url = preg_replace('/\?.*/', '', $current_active_url); // Remove query string
            if ($current_active_url == $menu_item_url_filtered) {
                $menu_class .= ' active';
                $menu_item_class .= ' current-menu-item';
            }
        }

        $localHTML = '';
        $localHTML .= '<li id="menu-item-' . $menu_item['id'] . '" class="' . $menu_item_class . '">';

        if ($menu_item_url) {
            $localHTML .= '<a class="' . $menu_class . '" target="' . $target . '" href="' . $menu_item_url . '"' . $data_attributes . '>' . Craft::t('olivemenus', $menu_item['name']) . '</a>';
        } else {
            $localHTML .= '<span class="' . $menu_class . '"' . $data_attributes . '>' . Craft::t('olivemenus', $menu_item['name']) . '</span>';
        }

        if (isset($menu_item['children'])) {

            if (isset($config['sub-menu-ul-class'])) {
                $ul_class = $config['sub-menu-ul-class'];
            }

            $localHTML .= '<ul class="' . $ul_class . '">';
            foreach ($menu_item['children'] as $child) {
                $localHTML .= $this->getMenuItemHTML($child, $config);
            }
            $localHTML .= '</ul>';
        }
        $localHTML .= '</li>';

        return $localHTML;
    }

    private function replaceEnvironmentVariables($str)
    {
        $environmentVariables = Craft::$app->config->general->aliases;
        if (is_array($environmentVariables)) {
            $tmp = [];
            foreach ($environmentVariables as $tag => $val) {
                $tmp[sprintf("{%s}", $tag)] = $val;
            }
            $environmentVariables = $tmp;

            return str_replace(array_keys($environmentVariables), array_values($environmentVariables), $str);
        }
    }
}
?>