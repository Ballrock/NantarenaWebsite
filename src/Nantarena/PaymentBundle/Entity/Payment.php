<?php

namespace Nantarena\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
 
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Payment
 *
 * @ORM\Entity(repositoryClass="Nantarena\PaymentBundle\Repository\PaymentRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="method", type="string")
 * @ORM\DiscriminatorMap({"classic" = "Payment", "paypal" = "PaypalPayment", "cash" = "CashPayment", "adaptative" = "AdaptativePayment"})
 * @ORM\Table(name="payment_payment")
 */
class Payment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Nantarena\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $valid;

    /**
     * @ORM\OneToMany(targetEntity="Nantarena\PaymentBundle\Entity\Transaction", mappedBy="payment", cascade={"persist", "remove"})
     */
    private $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    /**
     * Is valid
     *
     * @return boolean 
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Is refund
     *
     * @return boolean 
     */
    public function isValidRefund()
    {
        $toReturn = true;
        foreach ($this->transactions as $transaction) {
            if (!$transaction->isValidRefund()) {
                $toReturn = false;
                break;
            }
        }
        return $toReturn;
    }

    /**
     * Get amount
     */
    public function getAmount()
    {
        $total = 0.00;
        foreach ($this->transactions as $transaction) {
            $total += $transaction->getPrice();
        }
        return round($total, 2);
    }

    // "paypal" = "PaypalPayment", "cash" = "CashPayment", "adaptative" = "AdaptativePayment"})

    /**
     * Is Paypal
     *
     * @return boolean 
     */
    public function isPaypal()
    {
        return $this instanceof PaypalPayment;
    }

    /**
     * Is Cash
     *
     * @return boolean 
     */
    public function isCash()
    {
        return $this instanceof CashPayment;
    }

    /**
     * Is Adaptative
     *
     * @return boolean 
     */
    public function isAdaptative()
    {
        return $this instanceof AdaptativePayment;
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Payment
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set valid
     *
     * @param boolean $valid
     * @return Payment
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    
        return $this;
    }

    /**
     * Get valid
     *
     * @return boolean 
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Set user
     *
     * @param \Nantarena\UserBundle\Entity\User $user
     * @return Payment
     */
    public function setUser(\Nantarena\UserBundle\Entity\User $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Nantarena\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add transactions
     *
     * @param \Nantarena\PaymentBundle\Entity\Transaction $transactions
     * @return Payment
     */
    public function addTransaction(\Nantarena\PaymentBundle\Entity\Transaction $transactions)
    {
        $transactions->setPayment($this);
        $this->transactions[] = $transactions;
    
        return $this;
    }

    /**
     * Remove transactions
     *
     * @param \Nantarena\PaymentBundle\Entity\Transaction $transactions
     */
    public function removeTransaction(\Nantarena\PaymentBundle\Entity\Transaction $transactions)
    {
        $this->transactions->removeElement($transactions);
    }

    /**
     * Get transactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTransactions()
    {
        return $this->transactions;
    }
}
