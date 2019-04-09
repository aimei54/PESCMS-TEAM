<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.6
 * @version 1.0
 */


namespace Slice\Team;

/**
 * 后台验证权限
 */
class Auth extends \Core\Slice\Slice{

    public function before() {
        if($this->session()->get('team')['user_id'] == '1'){
            return true;
        }
        $findNode = \Model\Content::findContent('node', GROUP . METHOD . MODULE . ACTION, 'node_check_value');
        if(empty($findNode)){
            return true;
        }

        $list = \Model\Content::listContent([
            'table' => 'node_group',
            'condition' => 'user_group_id = :user_group_id AND node_id = :node_id',
            'param' => [
                'user_group_id' => $this->session()->get('team')['user_group_id'],
                'node_id' => $findNode['node_id']
            ]
        ]);
        if(empty($list)){
            $this->error(empty($findNode['node_msg']) ? '您的权限不足' : $findNode['node_msg']);
        }
    }

    public function after() {
    }


}