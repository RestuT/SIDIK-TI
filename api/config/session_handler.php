<?php
/**
 * Firestore Custom Session Handler
 * Enables persistent sessions in serverless environments like Vercel.
 */

class FirestoreSessionHandler implements SessionHandlerInterface {
    private $db;
    private $collection = 'sessions';

    public function __construct($firestoreInstance) {
        $this->db = $firestoreInstance;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        try {
            $docRef = $this->db->collection($this->collection)->document($id);
            $snapshot = $docRef->snapshot();
            if ($snapshot->exists()) {
                return (string)$snapshot->get('data');
            }
        } catch (Exception $e) {
            error_log("Session Read Error: " . $e->getMessage());
        }
        return '';
    }

    public function write($id, $data): bool {
        try {
            $docRef = $this->db->collection($this->collection)->document($id);
            $docRef->set([
                'data' => $data,
                'timestamp' => new \Google\Cloud\Firestore\FieldValue(\Google\Cloud\Firestore\FieldValue::SERVER_TIMESTAMP)
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Session Write Error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $this->db->collection($this->collection)->document($id)->delete();
            return true;
        } catch (Exception $e) {
            error_log("Session Destroy Error: " . $e->getMessage());
            return false;
        }
    }

    public function gc($maxlifetime): int|bool {
        try {
            // Simple GC: Delete sessions older than $maxlifetime seconds
            $expirationTime = time() - $maxlifetime;
            $query = $this->db->collection($this->collection)
                ->where('timestamp', '<', new DateTime("@$expirationTime"));
            
            $documents = $query->documents();
            $count = 0;
            foreach ($documents as $document) {
                $document->reference()->delete();
                $count++;
            }
            return $count;
        } catch (Exception $e) {
            error_log("Session GC Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
