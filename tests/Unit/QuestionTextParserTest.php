<?php

namespace Tests\Unit;

use App\Services\QuestionImport\QuestionTextParser;
use PHPUnit\Framework\TestCase;

class QuestionTextParserTest extends TestCase
{
    public function test_parses_pdf_questions_with_passage_and_missing_answers_as_review(): void
    {
        $parser = new QuestionTextParser();

        $questions = $parser->parse(<<<'TEXT'
Read the text carefully, to answer no. 1 to 3.
A poor merchant owed a wicked moneylender a large sum of money.
1. The story is mainly about __________
A. White and black pebbles
B. A clever girl and a wicked moneylender
C. A misfortune merchant and his daughter
D. A clever moneylender and a dull girl
E. A merchant and a genial moneylender
TEXT);

        $this->assertCount(1, $questions);
        $this->assertStringContainsString('A poor merchant owed', $questions[0]['question_text']);
        $this->assertStringContainsString('The story is mainly about', $questions[0]['question_text']);
        $this->assertSame('A merchant and a genial moneylender', $questions[0]['options']['E']);
        $this->assertNull($questions[0]['correct_answer']);
        $this->assertTrue($questions[0]['needs_review']);
    }

    public function test_supports_option_label_variations_and_textual_answer_keys(): void
    {
        $parser = new QuestionTextParser();

        $questions = $parser->parse(<<<'TEXT'
1. Which word has the closest meaning to clever?
A) Smart
B) Slow
C) Angry
D) Silent
E) Empty
Correct Answer: A
2. Choose the synonym of wicked.
A Evil
B Kind
C Gentle
D Honest
E Friendly
D
3. The best title is ____
A.
The Clever Daughter
B.
The Empty Bag
C.
The Long Road
D.
The River Stone
E.
The Honest Trader
TEXT);

        $this->assertCount(3, $questions);
        $this->assertSame('A', $questions[0]['correct_answer']);
        $this->assertSame('D', $questions[1]['correct_answer']);
        $this->assertSame('Evil', $questions[1]['options']['A']);
        $this->assertSame('The Honest Trader', $questions[2]['options']['E']);
        $this->assertNull($questions[2]['correct_answer']);
    }

    public function test_attaches_trailing_passage_to_next_question_without_contaminating_previous_option(): void
    {
        $parser = new QuestionTextParser();

        $questions = $parser->parse(<<<'TEXT'
1. First standalone question?
A. One
B. Two
C. Three
D. Four
E. Five
Answer: B
Read the following text.
This paragraph belongs to question number two.
2. What does the paragraph describe?
A. A place
B. A person
C. An event
D. A tool
E. A rule
TEXT);

        $this->assertCount(2, $questions);
        $this->assertSame('Five', $questions[0]['options']['E']);
        $this->assertSame('B', $questions[0]['correct_answer']);
        $this->assertStringContainsString('This paragraph belongs', $questions[1]['question_text']);
        $this->assertStringNotContainsString('Read the following text', $questions[0]['options']['E']);
    }

    public function test_splits_options_when_pdf_extracts_multiple_choices_on_one_line(): void
    {
        $parser = new QuestionTextParser();

        $questions = $parser->parse(<<<'TEXT'
1. The story is mainly about __________ A. White and black pebbles B. A clever girl and a wicked moneylender C. A misfortune merchant and his daughter D. A clever moneylender and a dull girl E. A merchant and a genial moneylender
TEXT);

        $this->assertCount(1, $questions);
        $this->assertSame('The story is mainly about __________', $questions[0]['question_text']);
        $this->assertSame('White and black pebbles', $questions[0]['options']['A']);
        $this->assertSame('A clever girl and a wicked moneylender', $questions[0]['options']['B']);
        $this->assertSame('A merchant and a genial moneylender', $questions[0]['options']['E']);
    }
}
