<?php

use Phalcon\Mvc\Controller;
use MongoDB\BSON\ObjectID;

/**
 * Order Contoller class
 */
class OrderController extends Controller
{
    /**
     * orderList function
     * list all orders
     * @return void
     */
    public function orderListAction()
    {
        if ($this->request->has('status')) {
            $Order = new Orders($this->mongo, 'store', 'orders');
            $order = $Order->updateOne(
                ['_id' => new ObjectID((string) $this->request->getPost('id'))],
                [
                    '$set' => [
                        'Status' => $this->request->getPost('status'),
                    ]
                ]
            );
            printf("Matched %d document(s)\n", $order->getMatchedCount());
            printf("Modified %d document(s)\n", $order->getModifiedCount());
            // die();
        }

        

        // $this->view->t = $this->cache->get($this->request->get('locale'));
        $this->view->t = $this->cache->get('en');

        $orders = new Orders($this->mongo, 'store', 'orders');
        $this->view->orders = $orders->find();
    }

    /**
     * addOrder function
     * add new order
     * @return void
     */
    public function addOrderAction()
    {
        $this->view->t = $this->cache->get($this->request->get('locale'));
        // $orders = new Orders($this->mongo, 'store', 'orders');
        $products = new Products($this->mongo, 'store', 'products');
        $this->view->products = $products->find();

        if ($this->request->has('id')) {
            $product = new Products($this->mongo, 'store', 'products');
            $product = $product->findOne(['_id' => new ObjectID((string) $this->request->get('id'))]);
            echo json_encode($product);
            die();
        }



        if ($this->request->has('customer_name')) {
            echo "asdf";
            // die();
            $newOrder = new Orders($this->mongo, 'store', 'orders');
            $myescaper = new App\Components\Myescaper();
            $myescaper = $myescaper->santize($this->request->getPost());
            $this->view->post = $this->request->getPost();
            $this->view->order = $myescaper;
            $myescaper['created_date'] = Date('Y-m-d');
            $newOrder->insertOne(
                $myescaper,
                // [
                //     'customer_name',
                //     'customer_address',
                //     'zipcode',
                //     'product',
                //     'quantity',
                //     'created_date'
                // ]
            );
            // $this->view->order = $newOrder;
            // $newOrder->save();
            // $eventsManager = $this->di->get('EventsManager');
            // $settings = Settings::find(1);
            // $this->view->event =   $eventsManager->fire('notification:beforesend', $newOrder, $settings);
            // $success = $this->view->event->save();
            // if ($success) {
            //     $this->view->msg = "<h6 class='alert alert-success w-75 container text-center'>Added Successfully</h6>";
            // } else {
            //     $this->view->msg = "<h6 class='alert alert-danger w-75 container text-center'>Something went wrong</h6>";
            // }
        }
    }

    /**
     * delete function
     * Delete a particular order
     * @return void
     */
    public function deleteAction()
    {
        $product = Orders::find($this->request->get('id'));

        $success = $product->delete();
        if ($success) {
            $this->response->redirect('http://localhost:8080/order/orderList?locale=en');
        } else {
            echo "Something went wrong";
        }
    }

    /**
     * editOrder function
     * edit and update a particular order
     * @return void
     */
    public function editOrderAction()
    {
        $this->view->t = $this->cache->get($this->request->get('locale'));
        $order = Orders::find($this->request->get('id'));
        $this->view->products = Products::find();
        $this->view->order = $order;
        if ($this->request->isPost('Update Order')) {
            $myescaper = new App\Components\Myescaper();
            $myescaper = $myescaper->santize($this->request->getPost());
            $order[0]->customer_name = $myescaper['customer_name'];
            $order[0]->customer_address = $myescaper['customer_address'];
            $order[0]->zipcode = $myescaper['zipcode'];
            $order[0]->product = $myescaper['product'];
            $order[0]->quantity = $myescaper['quantity'];
            $success = $order[0]->save();
            if ($success) {
                $this->view->msg = "<h6 class='alert alert-success w-75 container text-center'>Updated Successfully</h6>";
            } else {
                $this->view->msg = "<h6 class='alert alert-danger w-75 container text-center'>Something went wrong</h6>";
            }
        }
    }
}
