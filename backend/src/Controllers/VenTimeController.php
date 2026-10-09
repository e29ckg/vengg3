<?php

class VenTimeController
{
    public function __construct(private VenTimeModel $model) {}

    public function handle(): void
    {
        try {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    echo json_encode($this->model->list());
                    return;
                case 'POST':
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!is_array($data)) {
                        throw new InvalidArgumentException('ข้อมูลไม่ถูกต้อง');
                    }
                    $created = !isset($data['id']) || $data['id'] === '';
                    $id = $this->model->save($data);
                    if ($created) http_response_code(201);
                    echo json_encode(['success' => true, 'id' => $id]);
                    return;
                case 'DELETE':
                    $this->model->delete($_GET['id'] ?? null);
                    echo json_encode(['success' => true]);
                    return;
            }
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            echo json_encode(['error' => $error->getMessage()]);
        } catch (OutOfBoundsException $error) {
            http_response_code(404);
            echo json_encode(['error' => $error->getMessage()]);
        } catch (DomainException $error) {
            http_response_code(409);
            echo json_encode(['error' => $error->getMessage()]);
        }
    }
}
