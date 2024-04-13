<?php
/*
*
*/

class SearchRemedyForm extends ActiveForm
{
	public $start_with;
	public $potencies;
	public $keywords;
	public $vials;

	public function buildQuery()
{
	return new ActiveQuery();
}

}
