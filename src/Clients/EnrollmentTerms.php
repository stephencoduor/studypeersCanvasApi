<?php

namespace studypeers\CanvasApi\Clients;

/**
 * https://canvas.studypeers.com/doc/api/enrollment_terms.html
 */
class EnrollmentTerms implements CanvasApiClientInterface
{
    public function createEnrollmentTerm($account_id)
    {
        return [
            'accounts/' . $account_id . '/terms',
            'post'
        ];
    }

    public function updateEnrollmentTerm($account_id, $id)
    {
        return [
            'accounts/' . $account_id . '/terms/' . $id,
            'put'
        ];
    }

    public function deleteEnrollmentTerm($account_id, $id)
    {
        return [
            'accounts/' . $account_id . '/terms/' . $id,
            'delete'
        ];
    }

    public function listEnrollmentTerms($account_id)
    {
        return [
            'accounts/' . $account_id . '/terms',
            'get'
        ];
    }
}
