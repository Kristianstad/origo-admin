function updateSelect(id, array)
{
    let pattern = /Categories$/;
    let isCategories = pattern.test(id);
    let select = document.getElementById(id);
    if (select != null)
    {
        if (select.options != null)
        {
            var length = select.options.length;
            for (i = length-1; i >= 0; i--)
            {
                select.options[i] = null;
            }
        }
        array.forEach(function(item) {
            var newOption = document.createElement("option");
            if (isCategories)
            {
                newOption.value = item.toString();
                newOption.text = newOption.value.replaceAll('_', ' ');
            }
            else
            {
                newOption.text = item.toString();
            }
            select.add(newOption);
        });
    }
}
