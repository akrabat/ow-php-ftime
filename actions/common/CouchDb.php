<?php
namespace FTime;

use GuzzleHttp\Client;

class CouchDb
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct($baseUrl = null)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
        ]);
    }

    public function get($id)
    {
        try {
            $res = $this->client->request('GET', $id);
            return json_decode((string)$res->getBody(), true);
        } catch (\Exception $e) {
            error_log("Failed to get $id");
            error_log((string)$e->getResponse()->getBody());
            return null;
        }
    }

    public function delete($id, $rev = null)
    {
        if (!$rev) {
            $item = $this->get($id);
            if (!$item) {
                // item doesn't exist
                return true;
            }
            $rev = $item['_rev'];
        }

        try {
            $url = "$id?rev=$rev";
            $res = $this->client->request('DELETE', $url);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to delete $id (rev: $rev)");
            error_log((string)$e->getResponse()->getBody());
            return false;
        }
    }

    public function update($id, array $data)
    {
        $item = $this->get($id);
        $url = $id;
        if ($item) {
            $url .= '?rev=' . $item['_rev'];
        }

        try {
            $res = $this->client->request('PUT', $url, [
                'json' => $data,
            ]);
        } catch (\Exception $e) {
            if ($rev) {
                error_log("Failed to update $id (rev: $rev)");
            } else {
                error_log("Failed to insert $id");
            }
            error_log((string)$e->getResponse()->getBody());
            return false;
        }
        return true;
    }
}
