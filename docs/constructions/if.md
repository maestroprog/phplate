## Синтаксис
```
{? if <condition>;
	<код если истина>
[else
	<код если ложь>]
end ?}
```

## Описание
Позволяет изменять выводимый в конечный документ текст и выполняемые конструкции в зависимости от условия `<cond>`.

Блок кода `else` может отсутствовать. Допускается разделение ключевых слов `if`, `else` и `end` между разными блоками конструкций. Например:
```
{? if messages > 0 ?}
	Сообщения есть
{? else ?}
	Сообщений нет
{? end ?}
```