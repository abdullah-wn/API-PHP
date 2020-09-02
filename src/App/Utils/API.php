<?php

namespace App\Utils;

use Exception;
use App\DAOInterface;
use Express\Express;
use Express\Router;
use App\Utils\Entity\Entity;
use Express\Response;

class API
{
    // public function $$Events;
    const ON_REQUEST = 0;
    const ON_QUERY = 1;
    const ON_RESULT = 2;
    const ON_RESPONSE = 3;

    public $entities;
    private $config;
    private $dao;

    public function __construct(DAOInterface $dao = null)
    {
        $pathConfig = __DIR__ . "/../../../config.json";
        $this->config = json_decode(file_get_contents($pathConfig));
        $this->entities = EntityManager::getEntities(
            $this->config->parameters->path_entity,
            $this->config->parameters->path_model,
            $this->config->default
        );
        
        if(!$dao)
            $dao = new DAO();
        $this->dao = $dao;
    }

    public function getDao()
    {
        return $this->dao;
    }

    /*
    public function on(search, type.ON_REQUEST, action);
    public function on(search, type.ON_QUERY, action);
    public function on(search, type.ON_RESULT, action);
    public function on(search, type.ON_RESPONSE, action);
    */
    public function on($search, $type, $action)
    {
        $entityNames = null;
        $entities = [];
        if (is_string($search)) {
            $e = $this->entities->get($search);
            if ($e) {
                $entities[] = $e;
            } // Get entity object from string
        } elseif (is_a($search, 'RegExp')) {
            $entityNames = array_keys($entities);
            foreach ($entityNames as $entityName) {
                if ($search->test($entityName)) {
                    $e = $this->entities[$entityName];
                    if ($e) {
                        $entities[] = $e;
                    } // Get entity object from Regex
                }
            }
        } elseif (is_a($search, 'stdClass')) {
            throw new Exception('Invalid type for search');
        }

        foreach ($entities as $entity) {
            // Add event's listener and initialize event's listener's list if needed

            $eventListeners = $entity->eventListeners;
            if (!isset($eventListeners)) {
                $eventListeners = [
                    API::ON_REQUEST => [],
                    API::ON_QUERY => [],
                    API::ON_RESULT => [],
                    API::ON_RESPONSE => [],
                ];
            }
            switch ($type) {
                case API::ON_REQUEST:
                    $eventListeners[API::ON_REQUEST][] = $action;
                    break;
                case API::ON_QUERY:
                    $eventListeners[API::ON_QUERY][] = $action;
                    break;
                case API::ON_RESULT:
                    $eventListeners[API::ON_RESULT][] = $action;
                    break;
                case API::ON_RESPONSE:
                    $eventListeners[API::ON_RESPONSE][] = $action;
                    break;
            }
            $entity->eventListeners = $eventListeners;
        }
        return $this;
    }

    private function getEntity($entityName)
    {
        foreach ($this->entities as $key => $entity) {
            if ($entityName === strtolower($key)) {
                return $entity;
            }
        }

        return null;
    }

    public function createRoutes(Router $app)
    {
        $app->get('/:entity/:id', function ($req, $res) {
            $entity = $this->getEntity($req->params->entity);

            if ($entity) {
                API::executeActionsRequest($entity, $req, $res);
                if (!$this->dao) {
                    $this->dao = new DAO();
                }
                if ($res->headersSent) {
                    return;
                }
                $result = $this->dao->getById(
                    $this->entities,
                    $entity,
                    $req->params->id,
                    API::getOnQueryActions($entity),
                    API::getOnResultActions($entity)
                );
                API::executeActionsResponse($entity, $res, $result);
                if ($res->headersSent) {
                    return;
                }
                $res->json($result);
            } else {
                $res->send([
                    'code' => 404,
                    'message' => 'Not found',
                ]);
            }
        });

        $app->post('/:entity/', function ($req, $res) {
            $entity = $this->getEntity($req->params->entity);
            $result = [];

            if ($entity) {
                API::executeActionsRequest($entity, $req, $res);
                if ($res->headersSent) {
                    return;
                }
                if (!$this->dao) {
                    $this->dao = new DAO();
                }

                $data = $req->body;

                if (!is_array($data)) {
                    $data = [$data];
                }

                try {
                    foreach ($data as $item) {
                        $result[] = $this->dao->create(
                            $this->entities,
                            $entity,
                            $item,
                            API::getOnQueryActions($entity),
                            API::getOnResultActions($entity)
                        );
                    }
                    
                    API::executeActionsResponse($entity, $res, $result);
                    if ($res->headersSent) {
                        return;
                    }
                    $res->json($result);
                } catch (ValidationException $e) {
                    echo $e->getMessage();
                }

                //$this->handleResult($entity, $result, $res);
                // $this->handleError($res);
            } else {
                $res->json([
                    'code' => 404,
                    'message' => 'Not found',
                ]);
            }
        });

        $app->put('/:entity', function ($req, $res) {
            $entity = $this->getEntity($req->params->entity);

            if ($entity) {
                API::executeActionsRequest($entity, $req, $res);
                if (!$this->dao) {
                    $this->dao = new DAO();
                }

                try {
                    $result = $this->dao->update(
                        $this->entities,
                        $entity,
                        $req->body,
                        API::getOnQueryActions($entity),
                        API::getOnResultActions($entity)
                    );
                    
                    API::executeActionsResponse($entity, $res, $result);
                    // var_dump($result);
                    $res->json($result);
                } catch (ValidationException $e) {
                    echo $e->getMessage();
                }
            } else {
                $res->json([
                    'code' => 404,
                    'message' => 'Not found',
                ]);
            }
        });

        $app->delete('/:entity/:id', function ($req, $res) {
            $entity = $this->getEntity($req . params . entity);

            if (entity) {
                API::executeActionsRequest($entity, $req, $res);
                if ($res->headersSent) {
                    return;
                }
                if (!$this->dao) {
                    $this->dao = new DAO();
                }
                $result = $this->dao->delete(
                    $this->entities,
                    $entity,
                    $req->params->id,
                    API::getOnQueryActions($entity),
                    API::getOnResultActions($entity)
                );
                API::executeActionsResponse($entity, $res, $result);
                if ($res->headersSent) {
                    return;
                }
                $res->json($result);
                // catch($this->handleError($res));
            } else {
                $res->json([
                    'code' => 404,
                    'message' => 'Not found',
                ]);
            }
        });

        $app->get('/:entity/', function ($req, $res) {
            $entity = $this->getEntity($req->params->entity);

            if ($entity) {
                API::executeActionsRequest($entity, $req, $res);

                if (!$this->dao) {
                    $this->dao = new DAO();
                }
                $result = $this->dao->getAll(
                    $this->entities,
                    $entity,
                    API::getOnQueryActions($entity),
                    API::getOnResultActions($entity)
                );

                API::executeActionsResponse($entity, $res, $result);
                $res->json($result);
                // catch($this->handleError($res));
            } else {
                $res->json([
                    'code' => 404,
                    'message' => 'Not found',
                ]);
            }
        });
    }

    private function handleResult($entity, $result, $res)
    {
        API::executeActionsResponse($entity, $res, $result);
        if ($res->headersSent) {
            return;
        }
        return $res->send($result);
    }

    private function handleError($res)
    {
        return function ($errors) use ($res) {
            $res->status(400)->send($errors);
        };
    }

    static function executeActionsRequest(Entity $entity, $req, Response $res)
    {
        if (
            $entity->eventListeners &&
            $entity->eventListeners[API::ON_REQUEST]
        ) {
            $actions = $entity->eventListeners[API::ON_REQUEST];
            $i = 0;
            while ($i < count($actions) && !$res->headersSent) {
                $actions[$i]($req, $res);

                $i++;
            }
        }
    }

    static function executeActionsResponse(Entity $entity, Response $res, $result)
    {
        if (
            $entity->eventListeners &&
            $entity->eventListeners[API::ON_RESPONSE]
        ) {
            $actions = $entity->eventListeners[API::ON_RESPONSE];
            $i = 0;
            while ($i < count($actions) && !$res->headersSent) {
                $actions[$i]($res, $result);
                $i++;
            }
        }
    }

    static function getOnQueryActions(Entity $entity)
    {
        $actions = null;

        if ($entity->eventListeners && $entity->eventListeners[API::ON_QUERY]) {
            $actions = $entity->eventListeners[API::ON_QUERY];
        }

        return $actions;
    }

    static function getOnResultActions(Entity $entity)
    {
        $actions = null;

        if (
            $entity->eventListeners &&
            $entity->eventListeners[API::ON_RESULT]
        ) {
            $actions = $entity->eventListeners[API::ON_RESULT];
        }

        return $actions;
    }
}
