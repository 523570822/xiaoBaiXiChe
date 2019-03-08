<?php
/**
 * Created by PhpStorm.
 * User: 赵佳佳
 * Date: 2018/7/26
 * Time: 11:59
 */

namespace Manager\Controller;


class ArticleController extends BaseController {
    /**
     * 文章列表
     * User: jiajia
     * Date:2018/05/17 上午10:20
     */
    public function index () {
        $where = array ();
        if ( $_REQUEST['title'] ) {
            $where['title'] = $_REQUEST['title'];
        }
        if ( $_REQUEST['id'] ) {
            $where['id'] = $_REQUEST['id'];
        }
        $where['status'] = array ('lt' , 9);
        $param['order'] = 'sort desc , id  desc';
        $param['page_size'] = 15;
        $data = D ('Article')->queryList ($where , '*' , $param);
        $this->assign ($data);
        $this->display ();
    }

    /**
     * 添加文章
     * User:jiajia
     * Date: 2018/5/22 15:35
     */
    public function addArticle () {
        if ( IS_POST ) {
            $rule = array (
                array ('cate_id' , 'notnull') ,
                array ('id' , 'notnull') ,
                array ('title' , 'notnull') ,
                array ('sort' , 'notnull') ,
                array ('cover' , 'notnull') ,
                array ('content' , 'notnull')
            );
            $data = $this->checkParam ($rule);
            $data['admin_id'] = $this->userId;
            $data['add_time'] = time ();
            $Res = D ('Article')->add ($data);
            $Res ? $this->apiResponse (1 , '新增文章成功') : $this->apiResponse (0 , '新增文章失败');
        } else {
            $a_cate = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => 0))->select ();
            foreach ( $a_cate as $k => $v ) {
                $a_cate[$k]['s_cate'] = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => $v['id']))->select ();
            }
            $this->assign ('cate' , $a_cate);
            $this->display ('updateArt');
        }
    }

    /**
     * 编辑文章
     * User: jiajia
     * Date: 2018/5/22 15:39
     */
    public function editArticle () {
        if ( IS_POST ) {
            $rule = array (
                array ('cate_id' , 'notnull') ,
                array ('id' , 'notnull') ,
                array ('title' , 'notnull') ,
                array ('sort' , 'notnull') ,
                array ('cover' , 'notnull') ,
                array ('content' , 'notnull')
            );
            $data = $this->checkParam ($rule);
            $id = $data['id'];
            $data['admin_id'] = $this->userId;
            $Res = D ('Article')->where (array ('id' => $id))->save ($data);
            $Res ? $this->apiResponse (1 , '编辑文章成功') : $this->apiResponse (0 , '编辑文章失败');
        } else {
            $a_cate = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => 0))->select ();
            foreach ( $a_cate as $k => $v ) {
                $a_cate[$k]['s_cate'] = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => $v['id']))->select ();
            }
            $this->assign ('cate' , $a_cate);
            $id = I ('id');
            $web = D ('Article')->where (array ('id' => $id))->find ();
            $web['covers'] = $this->getOnePath ($web['cover']);//第二位放默认图
            $web['cates'] = D ('Article_cate')->where (array ('status' => 1 , 'id' => $web['cate_id']))->find ();
            $this->assign ('row' , $web);
            $this->display ('updateArt');
        }
    }

    /**
     * 删除文章
     * User: 木
     * Date: 2018/8/8 10:20
     */
    public function delArticle () {
        $id = $this->checkParam (array ('id' , 'int'));
        $Res = D ('Article')->where (array ('id' => $id))->save (array ('status' => 9));
        $Res ? $this->apiResponse (1 , '删除成功') : $this->apiResponse (0 , '删除失败');
    }

    /**
     * 锁定菜单
     * User: 木
     * Date: 2018/8/8 11:07
     */
    public function lockArticle () {
        $id = $this->checkParam (array ('id' , 'int'));
        $status = D ('Article')->where (array ('id' => $id))->getField ('status');
        $data = $status == 1 ? array ('status' => 0) : array ('status' => 1);
        $Res = D ('Article')->where (array ('id' => $id))->save ($data);
        $Res ? $this->apiResponse (1 , $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse (0 , $status == 1 ? '禁用失败' : '启用失败');
    }

    /**
     * 添加分类
     * User: admin
     * Date: 2018-08-16 16:33:28
     */
    public function Article_cate () {
        if ( IS_POST ) {
            $rule = array (
                array ('name' , 'notnull') ,
                array ('p_id' , 'notnull') ,
            );
            $data = $this->checkParam ($rule);
            $id = $data['id'];
            $Res = D ('ArticleCate')->where (array ('id' => $id))->add ($data);
            $Res ? $this->apiResponse (1 , '新增分类成功') : $this->apiResponse (0 , '新增分类失败');
        } else {
            $a_cate = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => 0))->select ();
            foreach ( $a_cate as $k => $v ) {
                $a_cate[$k]['s_cate'] = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => $v['id']))->select ();
            }
            $this->assign ('cate' , $a_cate);
            $this->display ();
        }
    }

    /**
     * 编辑分类
     * User: admin
     * Date: 2018-08-16 16:33:28
     */
    public function editArticlecate () {
        if ( IS_POST ) {
            $rule = array (
                array ('name' , 'notnull') ,
                array ('id' , 'notnull') ,
                array ('p_id' , 'notnull') ,
            );
            $data = $this->checkParam ($rule);
            $id = $data['id'];
            $Res = D ('ArticleCate')->where (array ('id' => $id))->save ($data);
            $Res ? $this->apiResponse (1 , '编辑分类成功') : $this->apiResponse (0 , '编辑分类失败');
        } else {
            $id = I ('id');
            $web = D ('ArticleCate')->where (array ('id' => $id))->find ();
            $web['p_cate'] = D ('Article_cate')->where (array ('status' => 1 , 'id' => $web['p_id']))->find ();
            $this->assign ('row' , $web);
            $a_cate = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => 0))->select ();
            foreach ( $a_cate as $k => $v ) {
                $a_cate[$k]['s_cate'] = D ('Article_cate')->where (array ('status' => 1 , 'p_id' => $v['id']))->select ();
            }
            $this->assign ('cate' , $a_cate);
            $this->display ('article_cate');
        }
    }

    /**
     * 文章分类列表
     * User: admin
     * Date: 2018-08-16 16:33:28
     */
    public function cateindexArticle () {
        $a_cate = D ('Article_cate')->where (array ('status' => array ("lt" , 9) , 'p_id' => 0))->select ();
        foreach ( $a_cate as $k => $v ) {
            $a_cate[$k]['s_cate'] = D ('Article_cate')->where (array ('status' => array ("lt" , 9) , 'p_id' => $v['id']))->select ();
        }
        $this->assign ('cate' , $a_cate);
        $this->display ();
    }

    /**
     * 删除文章分类
     * User: admin
     * Date: 2018-08-21 11:24:16
     */
    public function delArticlecate () {
        $id = $this->checkParam (array ('id' , 'int'));
        $Res = D ('ArticleCate')->where (array ('id' => $id))->save (array ('status' => 9));
        $Res ? $this->apiResponse (1 , '删除成功') : $this->apiResponse (0 , '删除失败');
    }

    /**
     * 锁定文章分类
     * User: admin
     * Date: 2018-08-21 11:19:41
     */
    public function lockArticlecate () {
        $id = $this->checkParam (array ('id' , 'int'));
        $status = D ('ArticleCate')->where (array ('id' => $id))->getField ('status');
        $data = $status == 1 ? array ('status' => 0) : array ('status' => 1);
        $Res = D ('ArticleCate')->where (array ('id' => $id))->save ($data);
        $Res ? $this->apiResponse (1 , $status == 1 ? '禁用成功' : '启用成功') : $this->apiResponse (0 , $status == 1 ? '禁用失败' : '启用失败');
    }
}