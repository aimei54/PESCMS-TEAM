<?php

/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace App\Team\PUT;

class Setting extends \Core\Controller\Controller {

    private $info = [];

    public function action() {
        foreach (['upload_img', 'upload_file'] as $value) {
            $data[$value] = json_encode(explode(',', str_replace(["\r\n", "\r", " "], '', $_POST[$value])));
        }

        $data['notice_way'] = $this->p('notice_way');
        $data['domain'] = $this->p('domain');
        $data['mail'] = json_encode($this->p('mail'));

        foreach ($data as $key => $value) {
            $this->db('option')->where('option_name = :option_name')->update(['value' => $value, 'noset' => ['option_name' => $key]]);
        }

        $this->success('保存设置成功!', $this->url('Team-Setting-action'));
    }

    /**
     * 自动更新
     * @todo 日后在弄
     */
    public function atUpgrade() {

    }

    /**
     * 手动更新
     */
    public function mtUpgrade() {
        $file = $_FILES['zip'];
        if (pathinfo($file['name'])['extension'] != 'zip') {
            $this->error('请导入zip的更新补丁');
        }

        /**
         * @todo 解压安装程序，这里没有做更新文件匹配，日后会补充对应验证，防止非法提权。
         */
        (new \Expand\zip()) ->unzip($file['tmp_name']);

        $this->actionini();
        
        $this->assign('info', $this->info);
        $this->layout('Setting_upgrade_info');
    }

    /**
     * 执行数据库更新
     * @return bool|string
     */
    private function actionini(){
        $version = \Core\Func\CoreFunc::$param['system']['version'];

        $ini = APP_PATH . 'Upgrade/action.ini';
        if (!file_exists($ini)) {
            return ['升级配置数据文件不存在'];
        }

        $ini_array = parse_ini_file($ini, true);


        foreach ($ini_array as $iniversion => $value) {
            if (str_replace('.', '', $iniversion) > str_replace('.', '', $version) ) {

                //更新SQL信息
                if (!empty($value['sql'])) {
                    foreach ($value['sql'] as $file) {
                        $sql = file_get_contents(APP_PATH.'/Upgrade/sql/'.$file);
                        if(!empty($sql)){
                            $this->db()->exec($sql);
                        }else{
                            //更新SQL文件失败，则记录起来
                            $this->info[] = "更新SQL文件出错: ".APP_PATH.'/Upgrade/sql/'.$file;
                        }
                    }
                }

                //移除废弃的文件(更名)
                if(!empty($value['delete'])){
                    foreach ($value['delete'] as $file) {
                        if(rename(APP_PATH.$file, APP_PATH.$file.'_remove') != true){
                            $this->info[] = "移除文件出错: ".APP_PATH.$file;
                        }
                    }
                }

                $this->db('option')->where('option_name = :option_name')->update([
                    'value' => $iniversion,
                    'noset' => [
                        'option_name' => 'version'
                    ]
                ]);
            }
        }
        //移除天网杀人的配置意识
        unlink($ini);
        return true;
    }

}