<?php

class Affiliate_WP_Recurring_IT_Exchange extends Affiliate_WP_Recurring_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->context = 'it-exchange';

		add_action( 'it_exchange_add_child_transaction', array( $this, 'record_referral_on_payment' ), -1 );

	}

	/**
	 * Insert referrals on subscription payments
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function record_referral_on_payment( $transaction_id ) {

		$parent_txn_id   = get_post_meta( $transaction_id, '_it_exchange_parent_tx_id', true );
		$parent_referral = affiliate_wp()->referrals->get_by( 'reference', $parent_txn_id, $this->context );

		if ( ! $parent_referral || ! is_object( $parent_referral ) || 'rejected' == $parent_referral->status ) {
			affiliate_wp()->utils->log( 'Recurring Referrals: No referral found or referral is rejected. Transaction ID: ' . $transaction_id );
			return false; // This signup wasn't referred or is the very first payment of a referred subscription
		}

		$transaction = get_post_meta( $transaction_id, '_it_exchange_cart_object', true );

		$amount       = $transaction->total;
		$reference    = $transaction_id;
		$affiliate_id = $parent_referral->affiliate_id;

		$referral_amount = $this->calc_referral_amount( $amount, $reference, $parent_referral->referral_id, '', $affiliate_id );

		/**
		 * Fires when the amount of a recurring referral is calculated.
		 *
		 * @param float $referral_amount  The referral amount.
		 * @param int   $affiliate_id     The affiliate ID.
		 * @param float $amount           The full transaction amount.
		 *
		 * @since 1.5
		 */
		$referral_amount = (string) apply_filters( 'affwp_recurring_calc_referral_amount', $referral_amount, $affiliate_id, $amount );

		$args = array(
			'reference'    => $transaction_id,
			'affiliate_id' => $affiliate_id,
			'description'  => sprintf( __( 'Subscription payment for %d', 'affiliate-wp-recurring-referrals' ), $parent_txn_id ),
			'amount'       => $referral_amount,
			'custom'       => $parent_txn_id,
			'parent_id'    => $parent_referral->referral_id
		);

		$referral_id = $this->insert_referral( $args );

		if ( $referral_id ) {

			$this->complete_referral( $referral_id );

		}

	}

}
new Affiliate_WP_Recurring_IT_Exchange;