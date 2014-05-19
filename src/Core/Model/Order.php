<?php

use Mnapoli\Translated\Translator;

/**
 * Tri dans une requête
 *
 * @author matthieu.napoli
 */
class Core_Model_Order
{
    /**
     * Ordre de tri ascendant.
     */
    const ORDER_ASC = 'ASC';
    /**
     * Ordre de tri descendant.
     */
    const ORDER_DESC = 'DESC';

    /**
     * Ensemble des tris.
     *
     * @var array(
     *     array(name, direction, alias)
     * )
     */
    protected $orders = [];


    /**
     * Ajoute un nouveau tri.
     *
     * @param string $name
     * @param string $direction Constante de la classe indiquant la direction du tri.
     * @param string $alias Alias sur l'objet concerné par la condition dans la requêtte DQL.
     *
     * @return void
     */
    public function addOrder($name, $direction = self::ORDER_ASC, $alias = null)
    {
        $this->orders[] = array(
            'name'      => $name,
            'direction' => $direction,
            'alias'     => $alias,
        );
    }


    /**
     * Ajoute un nouveau tri.
     *
     * @param string $name
     * @param string $direction Constante de la classe indiquant la direction du tri.
     * @param string $alias Alias sur l'objet concerné par la condition dans la requêtte DQL.
     *
     * @return void
     */
    public function addTranslatedOrder($name, $direction = self::ORDER_ASC, $alias = null)
    {
        /** @var Translator $translator */
        $translator = \Core\ContainerSingleton::getContainer()->get(Translator::class);

        $this->addOrder($name . '.' . $translator->getCurrentLocale(), $direction, $alias);
    }

    /**
     * Renvoie les tris.
     *
     * @return array(
     *  array(
     *      'name'      => $name,
     *      'direction' => $direction,
     *      'alias'     => $alias
     *  )
     * );
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Valide les attributs de la classe.
     */
    public function validate()
    {
        $ordersName = array();
        foreach ($this->orders as $order) {
            $tmpOrder = $order['alias'] . '.' . $order['name'];
            if (in_array($tmpOrder, $ordersName)) {
                throw new Core_Exception_InvalidArgument('Order for '.$order['name'].'" has already been specified.');
            }
            $ordersName[] = $tmpOrder;
            // Vérification de direction.
            if (($order['direction'] !== self::ORDER_ASC) && ($order['direction'] !== self::ORDER_DESC)) {
                throw new Core_Exception_InvalidArgument('Sort direction for "'.$order['name'].'" is invald.');
            }
        }

    }
}
