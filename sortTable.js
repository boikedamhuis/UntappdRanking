const table = document.querySelector("#rankingTable");

if (table) {
  const headers = [...table.querySelectorAll("th[data-sort]")];
  const tbody = table.querySelector("tbody");
  let activeIndex = null;
  let direction = "desc";

  const getCellValue = (row, index) => {
    const cell = row.children[index];
    return cell?.dataset.value ?? cell?.textContent.trim() ?? "";
  };

  const compareRows = (index, sortType) => (firstRow, secondRow) => {
    const first = getCellValue(firstRow, index);
    const second = getCellValue(secondRow, index);

    if (sortType === "number") {
      return Number(first) - Number(second);
    }

    return first.localeCompare(second, "nl", { sensitivity: "base" });
  };

  const renumberRanks = () => {
    [...tbody.rows].forEach((row, index) => {
      const rank = row.querySelector(".rank");
      if (rank) {
        rank.textContent = String(index + 1);
      }
    });
  };

  const sortByHeader = (header) => {
    const index = header.cellIndex;
    const sortType = header.dataset.sort;
    const rows = [...tbody.rows];
    const nextDirection = activeIndex === index && direction === "desc" ? "asc" : "desc";
    const multiplier = nextDirection === "asc" ? 1 : -1;

    rows
      .sort((firstRow, secondRow) => compareRows(index, sortType)(firstRow, secondRow) * multiplier)
      .forEach((row) => tbody.appendChild(row));

    headers.forEach((item) => {
      item.classList.toggle("is-sorted", item === header);
      item.removeAttribute("aria-sort");
    });

    header.setAttribute("aria-sort", nextDirection === "asc" ? "ascending" : "descending");
    activeIndex = index;
    direction = nextDirection;
    renumberRanks();
  };

  headers.forEach((header) => {
    header.tabIndex = 0;
    header.addEventListener("click", () => sortByHeader(header));
    header.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        sortByHeader(header);
      }
    });
  });

  sortByHeader(headers.find((header) => header.dataset.sort === "number") ?? headers[0]);
}
