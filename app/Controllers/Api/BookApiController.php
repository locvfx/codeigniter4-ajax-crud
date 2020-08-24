<?php

namespace App\Controllers\Api;

use App\Criteria\BookCriteria;
use App\Models\BookModel;
use App\Repository\BookRepository;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Config\Config;
use CodeIgniter\Controller;

class BookApiController extends Controller
{
    use ResponseTrait;

    protected $book;
    protected $pager;

    public function __construct()
    {
        $this->book = new BookRepository();
        $this->pager = Config::get('Pager');
    }

    /**
     * index.
     *
     * @return \CodeIgniter\Http\Response
     */
    public function index()
    {
        $resource = $this->book->scope($this->request)
            ->withCriteria([new BookCriteria()])
            ->paginate($this->pager->perPage, static::withSelect());

        return $this->respond(static::withResponse($resource));
    }

    /**
     * show.
     *
     * @return \CodeIgniter\Http\Response
     */
    public function show($id = null)
    {
        $resource = $this->book->withCriteria([new BookCriteria()])->find($id, static::withSelect());

        if (is_null($resource)) {
            return $this->failNotFound(sprintf('book with id %d not found', $id));
        }

        return $this->respond(['data' => $resource]);
    }

    /**
     * create.
     *
     * @return \CodeIgniter\Http\Response
     */
    public function create()
    {
        $request = $this->request->getPost(null, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$this->validate(static::rules())) {
            return $this->fail($this->validator->getErrors());
        }

        try {
            $resource = $this->book->create($request);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->respondCreated($resource);
    }

    /**
     * edit.
     *
     * @param int $id
     *
     * @return CodeIgniter\Http\Response
     */
    public function edit($id = null)
    {
        return $this->show($id);
    }

    /**
     * update.
     *
     * @param int $id
     *
     * @return CodeIgniter\Http\Response
     */
    public function update($id = null)
    {
        $request = $this->request->getRawInput();

        if (!$this->validate(static::rules())) {
            return $this->fail($this->validator->getErrors());
        }

        try {
            $this->book->update($request, $id);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->respondUpdated(['id' => $id], "book id {$id} updated");
    }

    /**
     * delete.
     *
     * @param int $id
     *
     * @return CodeIgniter\Http\Response
     */
    public function delete($id = null)
    {
        $this->respondDeleted($this->book->destroy($id));

        if ((new BookModel())->db->affectedRows() === 0) {
            return $this->failNotFound(sprintf('book with id %d not found or already deleted', $id));
        }

        return $this->respondDeleted(['id' => $id], "book id {$id} deleted");
    }

    /**
     * With response convert.
     *
     * @param array $resource
     *
     * @return array
     */
    protected static function withResponse(array $resource)
    {
        return [
            'data'     => $resource['data'],
            'paginate' => $resource['paginate']->getDetails(),
        ];
    }

    /**
     * With select.
     *
     * @return array
     */
    protected static function withSelect()
    {
        return [
            'books.id', 'books.status_id', 'status.status', 'books.title', 'books.author', 'books.description', 'books.created_at', 'books.updated_at',
        ];
    }

    /**
     * Rules set.
     *
     * @return array
     */
    protected static function rules()
    {
        return [
            'status_id'   => 'required|numeric',
            'title'       => 'required|min_length[10]|max_length[60]',
            'author'      => 'required|min_length[10]|max_length[200]',
            'description' => 'required|min_length[10]|max_length[200]',
        ];
    }
}
